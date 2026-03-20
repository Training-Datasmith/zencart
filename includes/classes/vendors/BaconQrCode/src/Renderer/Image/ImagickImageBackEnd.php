<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Renderer\Image;

use Bacon_Qr_Code\Exception\RuntimeException;
use Bacon_Qr_Code\Renderer\Color\Alpha;
use Bacon_Qr_Code\Renderer\Color\Cmyk;
use Bacon_Qr_Code\Renderer\Color\Color_Interface;
use Bacon_Qr_Code\Renderer\Color\Gray;
use Bacon_Qr_Code\Renderer\Color\Rgb;
use Bacon_Qr_Code\Renderer\Path\Close;
use Bacon_Qr_Code\Renderer\Path\Curve;
use Bacon_Qr_Code\Renderer\Path\Elliptic_Arc;
use Bacon_Qr_Code\Renderer\Path\Line;
use Bacon_Qr_Code\Renderer\Path\Move;
use Bacon_Qr_Code\Renderer\Path\Path;
use Bacon_Qr_Code\Renderer\Renderer_Style\Gradient;
use Bacon_Qr_Code\Renderer\Renderer_Style\Gradient_Type;
use Imagick;
use Imagick_Draw;
use Imagick_Pixel;
final class Imagick_Image_Back_End implements Image_Back_End_Interface
{
    private ?Imagick $image = null;
    private ?Imagick_Draw $draw = null;
    private ?int $gradient_count = null;
    /**
     * @var TransformationMatrix[]|null
     */
    private ?array $matrices = null;
    private ?int $matrix_index = null;
    public function __construct(private readonly string $image_format = 'png', private readonly int $compression_quality = 100)
    {
        if (!class_exists(Imagick::class)) {
            throw new RuntimeException('You need to install the imagick extension to use this back end');
        }
    }
    public function new(int $size, Color_Interface $background_color): void
    {
        $this->image = new Imagick();
        $this->image->new_image($size, $size, $this->get_color_pixel($background_color));
        $this->image->set_image_format($this->image_format);
        $this->image->set_compression_quality($this->compression_quality);
        $this->draw = new Imagick_Draw();
        $this->gradient_count = 0;
        $this->matrices = [new Transformation_Matrix()];
        $this->matrix_index = 0;
    }
    public function scale(float $size): void
    {
        if (null === $this->draw) {
            throw new RuntimeException('No image has been started');
        }
        $this->draw->scale($size, $size);
        $this->matrices[$this->matrix_index] = $this->matrices[$this->matrix_index]->multiply(Transformation_Matrix::scale($size));
    }
    public function translate(float $x, float $y): void
    {
        if (null === $this->draw) {
            throw new RuntimeException('No image has been started');
        }
        $this->draw->translate($x, $y);
        $this->matrices[$this->matrix_index] = $this->matrices[$this->matrix_index]->multiply(Transformation_Matrix::translate($x, $y));
    }
    public function rotate(int $degrees): void
    {
        if (null === $this->draw) {
            throw new RuntimeException('No image has been started');
        }
        $this->draw->rotate($degrees);
        $this->matrices[$this->matrix_index] = $this->matrices[$this->matrix_index]->multiply(Transformation_Matrix::rotate($degrees));
    }
    public function push(): void
    {
        if (null === $this->draw) {
            throw new RuntimeException('No image has been started');
        }
        $this->draw->push();
        $this->matrices[++$this->matrix_index] = $this->matrices[$this->matrix_index - 1];
    }
    public function pop(): void
    {
        if (null === $this->draw) {
            throw new RuntimeException('No image has been started');
        }
        $this->draw->pop();
        unset($this->matrices[$this->matrix_index--]);
    }
    public function draw_path_with_color(Path $path, Color_Interface $color): void
    {
        if (null === $this->draw) {
            throw new RuntimeException('No image has been started');
        }
        $this->draw->set_fill_color($this->get_color_pixel($color));
        $this->draw_path($path);
    }
    public function draw_path_with_gradient(Path $path, Gradient $gradient, float $x, float $y, float $width, float $height): void
    {
        if (null === $this->draw) {
            throw new RuntimeException('No image has been started');
        }
        $this->draw->set_fill_pattern_url('#' . $this->create_gradient_fill($gradient, $width, $height));
        $this->draw_path($path);
    }
    public function done(): string
    {
        if (null === $this->draw) {
            throw new RuntimeException('No image has been started');
        }
        $this->image->draw_image($this->draw);
        $blob = $this->image->get_image_blob();
        $this->draw->clear();
        $this->image->clear();
        $this->draw = null;
        $this->image = null;
        $this->gradient_count = null;
        return $blob;
    }
    private function draw_path(Path $path): void
    {
        $this->draw->path_start();
        foreach ($path as $op) {
            match (true) {
                $op instanceof Move => $this->draw->path_move_to_absolute($op->get_x(), $op->get_y()),
                $op instanceof Line => $this->draw->path_line_to_absolute($op->get_x(), $op->get_y()),
                $op instanceof Elliptic_Arc => $this->draw->path_elliptic_arc_absolute($op->get_x_radius(), $op->get_y_radius(), $op->get_x_axis_angle(), $op->is_large_arc(), $op->is_sweep(), $op->get_x(), $op->get_y()),
                $op instanceof Curve => $this->draw->path_curve_to_absolute($op->get_x1(), $op->get_y1(), $op->get_x2(), $op->get_y2(), $op->get_x3(), $op->get_y3()),
                $op instanceof Close => $this->draw->path_close(),
                default => throw new RuntimeException('Unexpected draw operation: ' . $op::class),
            };
        }
        $this->draw->path_finish();
    }
    private function create_gradient_fill(Gradient $gradient, float $width, float $height): string
    {
        [$width, $height] = $this->matrices[$this->matrix_index]->apply($width, $height);
        $start_color = $this->get_color_pixel($gradient->get_start_color())->get_color_as_string();
        $end_color = $this->get_color_pixel($gradient->get_end_color())->get_color_as_string();
        $gradient_image = new Imagick();
        switch ($gradient->get_type()) {
            case Gradient_Type::HORIZONTAL():
                $gradient_image->new_pseudo_image((int) $height, (int) $width, sprintf('gradient:%s-%s', $start_color, $end_color));
                $gradient_image->rotate_image('transparent', -90);
                break;
            case Gradient_Type::VERTICAL():
                $gradient_image->new_pseudo_image((int) $width, (int) $height, sprintf('gradient:%s-%s', $start_color, $end_color));
                break;
            case Gradient_Type::DIAGONAL():
            case Gradient_Type::INVERSE_DIAGONAL():
                $gradient_image->new_pseudo_image((int) ($width * sqrt(2)), (int) ($height * sqrt(2)), sprintf('gradient:%s-%s', $start_color, $end_color));
                if (Gradient_Type::DIAGONAL() === $gradient->get_type()) {
                    $gradient_image->rotate_image('transparent', -45);
                } else {
                    $gradient_image->rotate_image('transparent', -135);
                }
                $rotated_width = $gradient_image->get_image_width();
                $rotated_height = $gradient_image->get_image_height();
                $gradient_image->set_image_page($rotated_width, $rotated_height, 0, 0);
                $gradient_image->crop_image(intdiv($rotated_width, 2) - 2, intdiv($rotated_height, 2) - 2, intdiv($rotated_width, 4) + 1, intdiv($rotated_width, 4) + 1);
                break;
            case Gradient_Type::RADIAL():
                $gradient_image->new_pseudo_image((int) $width, (int) $height, sprintf('radial-gradient:%s-%s', $start_color, $end_color));
                break;
        }
        $id = sprintf('g%d', ++$this->gradient_count);
        $this->draw->push_pattern($id, 0, 0, $width, $height);
        $this->draw->composite(Imagick::COMPOSITE_COPY, 0, 0, $width, $height, $gradient_image);
        $this->draw->pop_pattern();
        return $id;
    }
    private function get_color_pixel(Color_Interface $color): Imagick_Pixel
    {
        $alpha = 100;
        if ($color instanceof Alpha) {
            $alpha = $color->get_alpha();
            $color = $color->get_base_color();
        }
        if ($color instanceof Rgb) {
            return new Imagick_Pixel(sprintf('rgba(%d, %d, %d, %F)', $color->get_red(), $color->get_green(), $color->get_blue(), $alpha / 100));
        }
        if ($color instanceof Cmyk) {
            return new Imagick_Pixel(sprintf('cmyka(%d, %d, %d, %d, %F)', $color->get_cyan(), $color->get_magenta(), $color->get_yellow(), $color->get_black(), $alpha / 100));
        }
        if ($color instanceof Gray) {
            return new Imagick_Pixel(sprintf('graya(%d%%, %F)', $color->get_gray(), $alpha / 100));
        }
        return $this->get_color_pixel(new Alpha($alpha, $color->to_rgb()));
    }
}