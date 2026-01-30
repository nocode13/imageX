<?php

namespace App\Console\Commands;

use App\Models\UserImage;
use GdImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class StressTestCommand extends Command
{
    protected $signature = 'app:stress-test
                            {--count=100 : Total images to upload}
                            {--rps=5 : Requests per second}
                            {--email=stress@test.com : User email}
                            {--password=password123 : User password}';

    protected $description = 'Stress test: upload unique images and monitor queue processing';

    private string $baseUrl = '';

    private string $token = '';

    public function handle(): int
    {
        /** @phpstan-ignore larastan.noEnvCallsOutsideOfConfig */
        $this->baseUrl = 'http://localhost:' . env('APP_PORT', 8000);

        $count = (int) $this->option('count');
        $rps = (int) $this->option('rps');
        $email = (string) $this->option('email');
        $password = (string) $this->option('password');

        $this->info('=== Stress Test: Unique Images Upload ===');
        $this->newLine();

        $this->table(['Parameter', 'Value'], [
            ['Total uploads', $count],
            ['Target RPS', $rps],
            ['Estimated time', gmdate('i:s', (int) ceil($count / $rps))],
            ['Simulates daily load', number_format($rps * 86400) . ' images/day'],
        ]);

        // Auth
        $token = $this->authenticate($email, $password);
        if ($token === null) {
            $this->error('Authentication failed');

            return 1;
        }
        $this->token = $token;
        $this->info("Authenticated as {$email}");
        $this->newLine();

        // Initial queue state
        $initialPending = UserImage::where('status', 'pending')->count();
        $this->info("Queue before test: {$initialPending} pending");
        $this->newLine();

        // Run test
        $results = $this->runTest($count, $rps);

        // Results
        $this->showResults($results, $count);

        // Monitor queue until empty
        $this->monitorQueue();

        return 0;
    }

    private function authenticate(string $email, string $password): ?string
    {
        $this->info("Connecting to: {$this->baseUrl}");

        try {
            $response = Http::timeout(10)->post("{$this->baseUrl}/api/auth/login", [
                'email' => $email,
                'password' => $password,
            ]);
        } catch (\Exception $e) {
            $this->error('Connection error: ' . $e->getMessage());

            return null;
        }

        if ($response->successful()) {
            $token = $response->json('data.token');
            if (is_string($token)) {
                return $token;
            }
        }

        $this->info('Registering new user...');
        $response = Http::timeout(10)->post("{$this->baseUrl}/api/auth/register", [
            'name' => 'Stress Test',
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        if (! $response->successful()) {
            $this->error('Register failed: ' . $response->status() . ' - ' . $response->body());

            return null;
        }

        $token = $response->json('data.token');

        return is_string($token) ? $token : null;
    }

    /**
     * @return array{success: int, failed: int, times: list<float>}
     */
    private function runTest(int $count, int $rps): array
    {
        $results = ['success' => 0, 'failed' => 0, 'times' => []];
        $delayMs = (int) (1000000 / $rps);

        $bar = $this->output->createProgressBar($count);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %elapsed:6s% | ~%remaining:6s% | %message%');
        $bar->setMessage('Starting...');
        $bar->start();

        $time = 0.0;

        for ($i = 1; $i <= $count; $i++) {
            $start = microtime(true);

            $imagePath = $this->generateUniqueImage($i);

            try {
                $response = Http::timeout(30)
                    ->withToken($this->token)
                    ->attach('image', File::get($imagePath), "test_{$i}.jpg")
                    ->post("{$this->baseUrl}/api/images");

                $time = (microtime(true) - $start) * 1000;
                $results['times'][] = $time;

                if ($response->successful()) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                }
            } catch (\Exception) {
                $results['failed']++;
            }

            File::delete($imagePath);

            $currentRps = $i / (microtime(true) - ($start - $time / 1000));
            $bar->setMessage(sprintf('RPS: %.1f', min($currentRps, $rps)));
            $bar->advance();

            $elapsed = (microtime(true) - $start) * 1000000;
            if ($elapsed < $delayMs) {
                usleep((int) ($delayMs - $elapsed));
            }
        }

        $bar->finish();
        $this->newLine(2);

        return $results;
    }

    private function generateUniqueImage(int $index): string
    {
        $width = 640;
        $height = 480;
        $image = imagecreatetruecolor($width, $height);

        if (! $image instanceof GdImage) {
            throw new \RuntimeException('Failed to create image');
        }

        $seed = $index + (int) (microtime(true) * 1000) + rand(0, 999999);
        mt_srand($seed);

        $r1 = mt_rand(0, 255);
        $g1 = mt_rand(0, 255);
        $b1 = mt_rand(0, 255);
        $r2 = mt_rand(0, 255);
        $g2 = mt_rand(0, 255);
        $b2 = mt_rand(0, 255);

        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / $height;
            $r = min(255, max(0, (int) ($r1 * (1 - $ratio) + $r2 * $ratio)));
            $g = min(255, max(0, (int) ($g1 * (1 - $ratio) + $g2 * $ratio)));
            $b = min(255, max(0, (int) ($b1 * (1 - $ratio) + $b2 * $ratio)));
            $color = imagecolorallocate($image, $r, $g, $b);
            if ($color !== false) {
                imageline($image, 0, $y, $width, $y, $color);
            }
        }

        for ($j = 0; $j < 15; $j++) {
            $color = imagecolorallocatealpha(
                $image,
                mt_rand(0, 255),
                mt_rand(0, 255),
                mt_rand(0, 255),
                mt_rand(30, 90)
            );

            if ($color === false) {
                continue;
            }

            if (mt_rand(0, 1)) {
                imagefilledellipse(
                    $image,
                    mt_rand(0, $width),
                    mt_rand(0, $height),
                    mt_rand(30, 150),
                    mt_rand(30, 150),
                    $color
                );
            } else {
                imagefilledrectangle(
                    $image,
                    mt_rand(0, $width),
                    mt_rand(0, $height),
                    mt_rand(0, $width),
                    mt_rand(0, $height),
                    $color
                );
            }
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $text = "#{$index} " . microtime(true);

        if ($black !== false) {
            imagestring($image, 5, 11, 11, $text, $black);
        }
        if ($white !== false) {
            imagestring($image, 5, 10, 10, $text, $white);
        }

        $path = storage_path("app/stress_{$index}_" . uniqid() . '.jpg');
        imagejpeg($image, $path, 85);

        return $path;
    }

    /**
     * @param array{success: int, failed: int, times: list<float>} $results
     */
    private function showResults(array $results, int $total): void
    {
        $this->info('=== Upload Results ===');

        $this->table(['Metric', 'Value'], [
            ['Successful uploads', $results['success']],
            ['Failed uploads', $results['failed']],
            ['Success rate', round($results['success'] / $total * 100, 1) . '%'],
        ]);

        if (count($results['times']) > 0) {
            $times = $results['times'];
            sort($times);
            $timesCount = count($times);

            $this->newLine();
            $this->info('Response Times (upload to API):');

            $p50Index = min((int) ($timesCount * 0.5), $timesCount - 1);
            $p95Index = min((int) ($timesCount * 0.95), $timesCount - 1);

            $this->table(['Metric', 'Value'], [
                ['Min', round(min($times)) . ' ms'],
                ['Avg', round(array_sum($times) / $timesCount) . ' ms'],
                ['p50', round($times[$p50Index]) . ' ms'],
                ['p95', round($times[$p95Index]) . ' ms'],
                ['Max', round(max($times)) . ' ms'],
            ]);
        }
    }

    private function monitorQueue(): void
    {
        $this->newLine();
        $this->info('=== Monitoring Queue Processing ===');
        $this->info('Press Ctrl+C to stop monitoring');
        $this->newLine();

        $startTime = time();

        while (true) {
            $pending = UserImage::where('status', 'pending')->count();
            $ready = UserImage::where('status', 'ready')->count();
            $failed = UserImage::where('status', 'failed')->count();

            $elapsed = time() - $startTime;
            $throughput = $elapsed > 0 ? round($ready / $elapsed, 1) : 0;

            $this->output->write("\r");
            $this->output->write(sprintf(
                'Pending: %s | Ready: %s | Failed: %s | Throughput: %s/sec | Elapsed: %s    ',
                str_pad((string) $pending, 5),
                str_pad((string) $ready, 5),
                str_pad((string) $failed, 3),
                str_pad((string) $throughput, 5),
                gmdate('i:s', $elapsed)
            ));

            if ($pending === 0) {
                $this->newLine(2);
                $this->info('All jobs processed in ' . gmdate('i:s', $elapsed));
                $this->info("Average throughput: {$throughput} images/sec");
                break;
            }

            sleep(1);
        }
    }
}
