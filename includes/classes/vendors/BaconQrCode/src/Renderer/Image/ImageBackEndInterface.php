<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Renderer\Image;

use Bacon_Qr_Code\Exception\RuntimeException;
use Bacon_Qr_Code\Renderer\Color\Color_Interface;
use Bacon_Qr_Code\Renderer\Path\Path;
use Bacon_Qr_Code\Renderer\Renderer_Style\Gradient;
/**
 * Interface for back ends able to to produce path based images.
 */
interface Image_Back_End_Interface
{
    /**
     * Starts a new image.
     *
     * If a previous image was already started, previous data get erased.
     */
    public function new(int $size, Color_Interface $background_color): void;
    /**
     * Transforms all following drawing operation coordinates by scaling them by a given factor.
     *
     * @throws RuntimeException if no image was started yet.
     */
    public function scale(float $size): void;
    /**
     * Transforms all following drawing operation coordinates by translating them by a given amount.
     *
     * @throws RuntimeException if no image was started yet.
     */
    public function translate(float $x, float $y): void;
    /**
     * Transforms all following drawing operation coordinates by rotating them by a given amount.
     *
     * @throws RuntimeException if no image was started yet.
     */
    public function rotate(int $degrees): void;
    /**
     * Pushes the current coordinate transformation onto a stack.
     *
     * @throws RuntimeException if no image was started yet.
     */
    public function push(): void;
    /**
     * Pops the last coordinate transformation from a stack.
     *
     * @throws RuntimeException if no image was started yet.
     */
    public function pop(): void;
    /**
     * Draws a path with a given color.
     *
     * @throws RuntimeException if no image was started yet.
     */
    public function draw_path_with_color(Path $path, Color_Interface $color): void;
    /**
     * Draws a path with a given gradient which spans the box described by the position and size.
     *
     * @throws RuntimeException if no image was started yet.
     */
    public function draw_path_with_gradient(Path $path, Gradient $gradient, float $x, float $y, float $width, float $height): void;
    /**
     * Ends the image drawing operation and returns the resulting blob.
     *
     * This should reset the state of the back end and thus this method should only be callable once per image.
     *
     * @throws RuntimeException if no image was started yet.
     */
    public function done(): string;
}