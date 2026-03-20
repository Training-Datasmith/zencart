<?php

declare (strict_types=1);
namespace Bacon_Qr_Code;

use Bacon_Qr_Code\Common\Error_Correction_Level;
use Bacon_Qr_Code\Common\Version;
use Bacon_Qr_Code\Encoder\Encoder;
use Bacon_Qr_Code\Exception\InvalidArgumentException;
use Bacon_Qr_Code\Renderer\Renderer_Interface;
/**
 * QR code writer.
 */
final readonly class Writer
{
    /**
     * Creates a new writer with a specific renderer.
     */
    public function __construct(private Renderer_Interface $renderer)
    {
    }
    /**
     * Writes QR code and returns it as string.
     *
     * Content is a string which *should* be encoded in UTF-8, in case there are
     * non ASCII-characters present.
     *
     * @throws InvalidArgumentException if the content is empty
     */
    public function write_string(string $content, string $encoding = Encoder::DEFAULT_BYTE_MODE_ENCODING, ?Error_Correction_Level $ec_level = null, ?Version $forced_version = null): string
    {
        if (strlen($content) === 0) {
            throw new InvalidArgumentException('Found empty contents');
        }
        if (null === $ec_level) {
            $ec_level = Error_Correction_Level::L();
        }
        return $this->renderer->render(Encoder::encode($content, $ec_level, $encoding, $forced_version));
    }
    /**
     * Writes QR code to a file.
     *
     * @see Writer::writeString()
     */
    public function write_file(string $content, string $filename, string $encoding = Encoder::DEFAULT_BYTE_MODE_ENCODING, ?Error_Correction_Level $ec_level = null, ?Version $forced_version = null): void
    {
        file_put_contents($filename, $this->write_string($content, $encoding, $ec_level, $forced_version));
    }
}