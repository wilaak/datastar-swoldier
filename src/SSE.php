<?php

namespace DatastarSwoldier;

use starfederation\datastar\enums\ElementPatchMode as OriginalElementPatchMode;

use starfederation\datastar\events\{
    EventInterface,
    ExecuteScript,
    Location,
    PatchElements,
    PatchSignals,
    RemoveElements,
};

use starfederation\datastar\Consts;

use Swoldier\HttpContext;

/**
 * Server-Sent Event (SSE) generator for Datastar Swoldier.
 */
class SSE
{
    /**
     * Constructor for the SSE generator.
     *
     * @param HttpContext $ctx The swoldier HTTP context containing the request and response objects.
     */
    public function __construct(
        private HttpContext $ctx,
    ) {}

    /**
     * Checks if the incoming request is a Datastar request.
     */
    public static function isDatastarRequest(HttpContext $ctx): bool
    {
        return $ctx->getHeader('datastar-request') !== null;
    }

    /**
     * Returns the signals sent in the incoming request.
     */
    public function readSignals(): array
    {
        $input = $this->ctx->getQueryParam(Consts::DATASTAR_KEY);
        $signals = $input ? \json_decode($input, true) : [];
        return \is_array($signals) ? $signals : [];
    }

    /**
     * Send the SSE response headers if they have not been sent yet.
     */
    public function sendHeaders(): void
    {
        if ($this->ctx->isCommitted()) {
            return;
        }

        $headers = [
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'text/event-stream',
            // Disable buffering for Nginx.
            // https://nginx.org/en/docs/http/ngx_http_proxy_module.html#proxy_buffering
            'X-Accel-Buffering' => 'no',
        ];

        // Connection-specific headers are only allowed in HTTP/1.1.
        // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Connection
        $proto = $this->ctx->getProtocol();
        if ($proto === 'HTTP/1.1') {
            $headers['Connection'] = 'keep-alive';
        }

        foreach ($headers as $name => $value) {
            $this->ctx->setHeader($name, $value);
        }

        // Initial SSE data to establish the stream.
        $this->ctx->write(":\n\n");
    }

    /**
     * Patches HTML elements into the DOM and returns the resulting output.
     *
     * @param array{
     *     selector?: string|null,
     *     mode?: ElementPatchMode|string|null,
     *     useViewTransition?: bool|null,
     *     eventId?: string|null,
     *     retryDuration?: int|null,
     * } $options
     */
    public function patchElements(string $elements, array $options = []): string
    {
        $mode = $options['mode'] ?? null;

        if ($mode) {
            $options['mode'] = OriginalElementPatchMode::from($mode->value);
        }

        return $this->sendEvent(new PatchElements($elements, $options));
    }

    /**
     * Patches signals and returns the resulting output.
     */
    public function patchSignals(array|string $signals, array $options = []): string
    {
        return $this->sendEvent(new PatchSignals($signals, $options));
    }

    /**
     * Removes elements from the DOM and returns the resulting output.
     *
     * @param array{
     *      eventId?: string|null,
     *      retryDuration?: int|null,
     *  } $options
     */
    public function removeElements(string $selector, array $options = []): string
    {
        return $this->sendEvent(new RemoveElements($selector, $options));
    }

    /**
     * Executes JavaScript in the browser and returns the resulting output.
     */
    public function executeScript(string $script, array $options = []): string
    {
        return $this->sendEvent(new ExecuteScript($script, $options));
    }

    /**
     * Redirects the browser by setting the location to the provided URI and returns the resulting output.
     */
    public function location(string $uri, array $options = []): string
    {
        return $this->sendEvent(new Location($uri, $options));
    }

    /**
     * Sends an event using the Swoldier HTTP context and returns the resulting output.
     */
    protected function sendEvent(EventInterface $event): string
    {
        if (!$this->ctx->isCommitted()) {
            $this->sendHeaders();
        }
        $output = $event->getOutput();
        $this->ctx->write($output);
        return $output;
    }
}
