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
final class Eps_Image_Back_End implements Image_Back_End_Interface
{
    private const PRECISION = 3;
    private ?string $eps = null;
    public function new(int $size, Color_Interface $background_color): void
    {
        $this->eps = "%!PS-Adobe-3.0 EPSF-3.0\n" . "%%Creator: BaconQrCode\n" . sprintf("%%%%BoundingBox: 0 0 %d %d \n", $size, $size) . "%%BeginProlog\n" . "save\n" . "50 dict begin\n" . "/q { gsave } bind def\n" . "/Q { grestore } bind def\n" . "/s { scale } bind def\n" . "/t { translate } bind def\n" . "/r { rotate } bind def\n" . "/n { newpath } bind def\n" . "/m { moveto } bind def\n" . "/l { lineto } bind def\n" . "/c { curveto } bind def\n" . "/z { closepath } bind def\n" . "/f { eofill } bind def\n" . "/rgb { setrgbcolor } bind def\n" . "/cmyk { setcmykcolor } bind def\n" . "/gray { setgray } bind def\n" . "%%EndProlog\n" . "1 -1 s\n" . sprintf("0 -%d t\n", $size);
        if ($background_color instanceof Alpha && 0 === $background_color->get_alpha()) {
            return;
        }
        $this->eps .= wordwrap('0 0 m' . sprintf(' %s 0 l', (string) $size) . sprintf(' %s %s l', (string) $size, (string) $size) . sprintf(' 0 %s l', (string) $size) . ' z' . ' ' . $this->get_color_set_string($background_color) . " f\n", 75, "\n ");
    }
    public function scale(float $size): void
    {
        if (null === $this->eps) {
            throw new RuntimeException('No image has been started');
        }
        $this->eps .= sprintf("%1\$s %1\$s s\n", round($size, self::PRECISION));
    }
    public function translate(float $x, float $y): void
    {
        if (null === $this->eps) {
            throw new RuntimeException('No image has been started');
        }
        $this->eps .= sprintf("%s %s t\n", round($x, self::PRECISION), round($y, self::PRECISION));
    }
    public function rotate(int $degrees): void
    {
        if (null === $this->eps) {
            throw new RuntimeException('No image has been started');
        }
        $this->eps .= sprintf("%d r\n", $degrees);
    }
    public function push(): void
    {
        if (null === $this->eps) {
            throw new RuntimeException('No image has been started');
        }
        $this->eps .= "q\n";
    }
    public function pop(): void
    {
        if (null === $this->eps) {
            throw new RuntimeException('No image has been started');
        }
        $this->eps .= "Q\n";
    }
    public function draw_path_with_color(Path $path, Color_Interface $color): void
    {
        if (null === $this->eps) {
            throw new RuntimeException('No image has been started');
        }
        $from_x = 0;
        $from_y = 0;
        $this->eps .= wordwrap('n ' . $this->draw_path_operations($path, $from_x, $from_y) . ' ' . $this->get_color_set_string($color) . " f\n", 75, "\n ");
    }
    public function draw_path_with_gradient(Path $path, Gradient $gradient, float $x, float $y, float $width, float $height): void
    {
        if (null === $this->eps) {
            throw new RuntimeException('No image has been started');
        }
        $from_x = 0;
        $from_y = 0;
        $this->eps .= wordwrap('q n ' . $this->draw_path_operations($path, $from_x, $from_y) . "\n", 75, "\n ");
        $this->create_gradient_fill($gradient, $x, $y, $width, $height);
    }
    public function done(): string
    {
        if (null === $this->eps) {
            throw new RuntimeException('No image has been started');
        }
        $this->eps .= "%%TRAILER\nend restore\n%%EOF";
        $blob = $this->eps;
        $this->eps = null;
        return $blob;
    }
    private function draw_path_operations(iterable $ops, &$from_x, &$from_y): string
    {
        $path_data = [];
        foreach ($ops as $op) {
            switch (true) {
                case $op instanceof Move:
                    $from_x = $to_x = round($op->get_x(), self::PRECISION);
                    $from_y = $to_y = round($op->get_y(), self::PRECISION);
                    $path_data[] = sprintf('%s %s m', $to_x, $to_y);
                    break;
                case $op instanceof Line:
                    $from_x = $to_x = round($op->get_x(), self::PRECISION);
                    $from_y = $to_y = round($op->get_y(), self::PRECISION);
                    $path_data[] = sprintf('%s %s l', $to_x, $to_y);
                    break;
                case $op instanceof Elliptic_Arc:
                    $path_data[] = $this->draw_path_operations($op->to_curves($from_x, $from_y), $from_x, $from_y);
                    break;
                case $op instanceof Curve:
                    $x1 = round($op->get_x1(), self::PRECISION);
                    $y1 = round($op->get_y1(), self::PRECISION);
                    $x2 = round($op->get_x2(), self::PRECISION);
                    $y2 = round($op->get_y2(), self::PRECISION);
                    $from_x = $x3 = round($op->get_x3(), self::PRECISION);
                    $from_y = $y3 = round($op->get_y3(), self::PRECISION);
                    $path_data[] = sprintf('%s %s %s %s %s %s c', $x1, $y1, $x2, $y2, $x3, $y3);
                    break;
                case $op instanceof Close:
                    $path_data[] = 'z';
                    break;
                default:
                    throw new RuntimeException('Unexpected draw operation: ' . $op::class);
            }
        }
        return implode(' ', $path_data);
    }
    private function create_gradient_fill(Gradient $gradient, float $x, float $y, float $width, float $height): void
    {
        $start_color = $gradient->get_start_color();
        $end_color = $gradient->get_end_color();
        if ($start_color instanceof Alpha) {
            $start_color = $start_color->get_base_color();
        }
        $start_color_type = $start_color::class;
        if (!in_array($start_color_type, [Rgb::class, Cmyk::class, Gray::class])) {
            $start_color_type = Cmyk::class;
            $start_color = $start_color->to_cmyk();
        }
        if ($end_color::class !== $start_color_type) {
            switch ($start_color_type) {
                case Cmyk::class:
                    $end_color = $end_color->to_cmyk();
                    break;
                case Rgb::class:
                    $end_color = $end_color->to_rgb();
                    break;
                case Gray::class:
                    $end_color = $end_color->to_gray();
                    break;
            }
        }
        $this->eps .= "eoclip\n<<\n";
        if ($gradient->get_type() === Gradient_Type::RADIAL()) {
            $this->eps .= " /ShadingType 3\n";
        } else {
            $this->eps .= " /ShadingType 2\n";
        }
        $this->eps .= " /Extend [ true true ]\n" . " /AntiAlias true\n";
        switch ($start_color_type) {
            case Cmyk::class:
                $this->eps .= " /ColorSpace /DeviceCMYK\n";
                break;
            case Rgb::class:
                $this->eps .= " /ColorSpace /DeviceRGB\n";
                break;
            case Gray::class:
                $this->eps .= " /ColorSpace /DeviceGray\n";
                break;
        }
        switch ($gradient->get_type()) {
            case Gradient_Type::HORIZONTAL():
                $this->eps .= sprintf(" /Coords [ %s %s %s %s ]\n", round($x, self::PRECISION), round($y, self::PRECISION), round($x + $width, self::PRECISION), round($y, self::PRECISION));
                break;
            case Gradient_Type::VERTICAL():
                $this->eps .= sprintf(" /Coords [ %s %s %s %s ]\n", round($x, self::PRECISION), round($y, self::PRECISION), round($x, self::PRECISION), round($y + $height, self::PRECISION));
                break;
            case Gradient_Type::DIAGONAL():
                $this->eps .= sprintf(" /Coords [ %s %s %s %s ]\n", round($x, self::PRECISION), round($y, self::PRECISION), round($x + $width, self::PRECISION), round($y + $height, self::PRECISION));
                break;
            case Gradient_Type::INVERSE_DIAGONAL():
                $this->eps .= sprintf(" /Coords [ %s %s %s %s ]\n", round($x, self::PRECISION), round($y + $height, self::PRECISION), round($x + $width, self::PRECISION), round($y, self::PRECISION));
                break;
            case Gradient_Type::RADIAL():
                $center_x = ($x + $width) / 2;
                $center_y = ($y + $height) / 2;
                $this->eps .= sprintf(" /Coords [ %s %s 0 %s %s %s ]\n", round($center_x, self::PRECISION), round($center_y, self::PRECISION), round($center_x, self::PRECISION), round($center_y, self::PRECISION), round(max($width, $height) / 2, self::PRECISION));
                break;
        }
        $this->eps .= " /Function\n" . " <<\n" . "  /FunctionType 2\n" . "  /Domain [ 0 1 ]\n" . sprintf("  /C0 [ %s ]\n", $this->get_color_string($start_color)) . sprintf("  /C1 [ %s ]\n", $this->get_color_string($end_color)) . "  /N 1\n" . " >>\n>>\nshfill\nQ\n";
    }
    private function get_color_set_string(Color_Interface $color): string
    {
        if ($color instanceof Rgb) {
            return $this->get_color_string($color) . ' rgb';
        }
        if ($color instanceof Cmyk) {
            return $this->get_color_string($color) . ' cmyk';
        }
        if ($color instanceof Gray) {
            return $this->get_color_string($color) . ' gray';
        }
        return $this->get_color_set_string($color->to_cmyk());
    }
    private function get_color_string(Color_Interface $color): string
    {
        if ($color instanceof Rgb) {
            return sprintf('%s %s %s', $color->get_red() / 255, $color->get_green() / 255, $color->get_blue() / 255);
        }
        if ($color instanceof Cmyk) {
            return sprintf('%s %s %s %s', $color->get_cyan() / 100, $color->get_magenta() / 100, $color->get_yellow() / 100, $color->get_black() / 100);
        }
        if ($color instanceof Gray) {
            return sprintf('%s', $color->get_gray() / 100);
        }
        return $this->get_color_string($color->to_cmyk());
    }
}