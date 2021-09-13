<?php

namespace App\Commands;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;

class Link extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'link {link : A share link from Spotify, Apple Music etc.} {--C|copy : Copy the link to your clipboard}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Return a Songwhip link';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $url = $this->getUrl();

        $this->copy($url);

        $this->info($url);
    }

    /**
     * @return string
     */
    private function getUrl(): string
    {
        $url = Arr::get($this->songwhipRequest(), 'url', []);

        return $url;
    }

    /**
     * @param  string  $url
     */
    private function copy(string $url): void
    {
        if ($this->option('copy')) {
            try {
                exec("echo '$url' | pbcopy");
            } catch (\Exception $exception) {
                $this->reportError($exception);
            }
        }
    }

    /**
     * @return array
     */
    private function songwhipRequest(): array
    {
        $link = $this->argument('link');

        try {
            return Cache::rememberForever(Str::slug($link), function () use ($link) {
                return Http::post(
                    'https://songwhip.com/',
                    [
                        'url' => $link,
                    ]
                )->json();
            });
        } catch (\Exception $exception) {
            $this->reportError($exception);
        }
    }

    /**
     * @param  null    $exception
     * @param  string  $message
     */
    private function reportError($exception = null, $message = 'An error has occurred'): void
    {
        if (config('app.env') === 'development') {
            $this->error($exception->getMessage());
            exit;
        } else {
            abort(500, $message);
            exit;
        }
    }
}
