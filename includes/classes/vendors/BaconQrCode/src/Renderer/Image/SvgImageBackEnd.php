<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Renderer\Image;

use Bacon_Qr_Code\Exception\RuntimeException;
use Bacon_Qr_Code\Renderer\Color\Alpha;
use Bacon_Qr_Code\Renderer\Color\Color_Interface;
use Bacon_Qr_Code\Renderer\Path\Close;
use Bacon_Qr_Code\Renderer\Path\Curve;
use Bacon_Qr_Code\Renderer\Path\Elliptic_Arc;
use Bacon_Qr_Code\Renderer\Path\Line;
use Bacon_Qr_Code\Renderer\Path\Move;
use Bacon_Qr_Code\Renderer\Path\Path;
use Bacon_Qr_Code\Renderer\Renderer_Style\Gradient;
use Bacon_Qr_Code\Renderer\Renderer_Style\Gradient_Type;
use Xml_Writer;
final class Svg_Image_Back_End implements Image_Back_End_Interface
{
    private const PRECISION = 3;
    private const SCALE_FORMAT = 'scale(%.' . self::PRECISION . 'F)';
    private const TRANSLATE_FORMAT = 'translate(%.' . self::PRECISION . 'F,%.' . self::PRECISION . 'F)';
    private ?Xml_Writer $xml_writer = null;
    private ?array $stack = null;
    private ?int $current_stack = null;
    private ?int $gradient_count = null;
    public function __construct()
    {
        if (!class_exists(Xml_Writer::class)) {
            throw new RuntimeException('You need to install the libxml extension to use this back end');
        }
    }
    public function new(int $size, Color_Interface $background_color): void
    {
        $this->xml_writer = new Xml_Writer();
        $this->xml_writer->open_memory();
        $this->xml_writer->start_document('1.0', 'UTF-8');
        $this->xml_writer->start_element('svg');
        $this->xml_writer->write_attribute('xmlns', 'http://www.w3.org/2000/svg');
        $this->xml_writer->write_attribute('version', '1.1');
        $this->xml_writer->write_attribute('width', (string) $size);
        $this->xml_writer->write_attribute('height', (string) $size);
        $this->xml_writer->write_attribute('viewBox', '0 0 ' . $size . ' ' . $size);
        $this->gradient_count = 0;
        $this->current_stack = 0;
        $this->stack[0] = 0;
        $alpha = 1;
        if ($background_color instanceof Alpha) {
            $alpha = $background_color->get_alpha() / 100;
        }
        if (0 === $alpha) {
            return;
        }
        $this->xml_writer->start_element('rect');
        $this->xml_writer->write_attribute('x', '0');
        $this->xml_writer->write_attribute('y', '0');
        $this->xml_writer->write_attribute('width', (string) $size);
        $this->xml_writer->write_attribute('height', (string) $size);
        $this->xml_writer->write_attribute('fill', $this->get_color_string($background_color));
        if ($alpha < 1) {
            $this->xml_writer->write_attribute('fill-opacity', (string) $alpha);
        }
        $this->xml_writer->end_element();
    }
    public function scale(float $size): void
    {
        if (null === $this->xml_writer) {
            throw new RuntimeException('No image has been started');
        }
        $this->xml_writer->start_element('g');
        $this->xml_writer->write_attribute('transform', sprintf(self::SCALE_FORMAT, round($size, self::PRECISION)));
        ++$this->stack[$this->current_stack];
    }
    public function translate(float $x, float $y): void
    {
        if (null === $this->xml_writer) {
            throw new RuntimeException('No image has been started');
        }
        $this->xml_writer->start_element('g');
        $this->xml_writer->write_attribute('transform', sprintf(self::TRANSLATE_FORMAT, round($x, self::PRECISION), round($y, self::PRECISION)));
        ++$this->stack[$this->current_stack];
    }
    public function rotate(int $degrees): void
    {
        if (null === $this->xml_writer) {
            throw new RuntimeException('No image has been started');
        }
        $this->xml_writer->start_element('g');
        $this->xml_writer->write_attribute('transform', sprintf('rotate(%d)', $degrees));
        ++$this->stack[$this->current_stack];
    }
    public function push(): void
    {
        if (null === $this->xml_writer) {
            throw new RuntimeException('No image has been started');
        }
        $this->xml_writer->start_element('g');
        $this->stack[] = 1;
        ++$this->current_stack;
    }
    public function pop(): void
    {
        if (null === $this->xml_writer) {
            throw new RuntimeException('No image has been started');
        }
        for ($i = 0; $i < $this->stack[$this->current_stack]; ++$i) {
            $this->xml_writer->end_element();
        }
        array_pop($this->stack);
        --$this->current_stack;
    }
    public function draw_path_with_color(Path $path, Color_Interface $color): void
    {
        if (null === $this->xml_writer) {
            throw new RuntimeException('No image has been started');
        }
        $alpha = 1;
        if ($color instanceof Alpha) {
            $alpha = $color->get_alpha() / 100;
        }
        $this->start_path_element($path);
        $this->xml_writer->write_attribute('fill', $this->get_color_string($color));
        if ($alpha < 1) {
            $this->xml_writer->write_attribute('fill-opacity', (string) $alpha);
        }
        $this->xml_writer->end_element();
    }
    public function draw_path_with_gradient(Path $path, Gradient $gradient, float $x, float $y, float $width, float $height): void
    {
        if (null === $this->xml_writer) {
            throw new RuntimeException('No image has been started');
        }
        $gradient_id = $this->create_gradient_fill($gradient, $x, $y, $width, $height);
        $this->start_path_element($path);
        $this->xml_writer->write_attribute('fill', 'url(#' . $gradient_id . ')');
        $this->xml_writer->end_element();
    }
    public function done(): string
    {
        if (null === $this->xml_writer) {
            throw new RuntimeException('No image has been started');
        }
        foreach ($this->stack as $open_elements) {
            for ($i = $open_elements; $i > 0; --$i) {
                $this->xml_writer->end_element();
            }
        }
        $this->xml_writer->end_document();
        $blob = $this->xml_writer->output_memory(true);
        $this->xml_writer = null;
        $this->stack = null;
        $this->current_stack = null;
        $this->gradient_count = null;
        return $blob;
    }
    private function start_path_element(Path $path): void
    {
        $path_data = [];
        foreach ($path as $op) {
            $path_data[] = match (true) {
                $op instanceof Move => sprintf('M%s %s', round($op->get_x(), self::PRECISION), round($op->get_y(), self::PRECISION)),
                $op instanceof Line => sprintf('L%s %s', round($op->get_x(), self::PRECISION), round($op->get_y(), self::PRECISION)),
                $op instanceof Elliptic_Arc => sprintf('A%s %s %s %u %u %s %s', round($op->get_x_radius(), self::PRECISION), round($op->get_y_radius(), self::PRECISION), round($op->get_x_axis_angle(), self::PRECISION), $op->is_large_arc(), $op->is_sweep(), round($op->get_x(), self::PRECISION), round($op->get_y(), self::PRECISION)),
                $op instanceof Curve => sprintf('C%s %s %s %s %s %s', round($op->get_x1(), self::PRECISION), round($op->get_y1(), self::PRECISION), round($op->get_x2(), self::PRECISION), round($op->get_y2(), self::PRECISION), round($op->get_x3(), self::PRECISION), round($op->get_y3(), self::PRECISION)),
                $op instanceof Close => 'Z',
                default => throw new RuntimeException('Unexpected draw operation: ' . $op::class),
            };
        }
        $this->xml_writer->start_element('path');
        $this->xml_writer->write_attribute('fill-rule', 'evenodd');
        $this->xml_writer->write_attribute('d', implode('', $path_data));
    }
    private function create_gradient_fill(Gradient $gradient, float $x, float $y, float $width, float $height): string
    {
        $this->xml_writer->start_element('defs');
        $start_color = $gradient->get_start_color();
        $end_color = $gradient->get_end_color();
        if ($gradient->get_type() === Gradient_Type::RADIAL()) {
            $this->xml_writer->start_element('radialGradient');
        } else {
            $this->xml_writer->start_element('linearGradient');
        }
        $this->xml_writer->write_attribute('gradientUnits', 'userSpaceOnUse');
        switch ($gradient->get_type()) {
            case Gradient_Type::HORIZONTAL():
                $this->xml_writer->write_attribute('x1', (string) round($x, self::PRECISION));
                $this->xml_writer->write_attribute('y1', (string) round($y, self::PRECISION));
                $this->xml_writer->write_attribute('x2', (string) round($x + $width, self::PRECISION));
                $this->xml_writer->write_attribute('y2', (string) round($y, self::PRECISION));
                break;
            case Gradient_Type::VERTICAL():
                $this->xml_writer->write_attribute('x1', (string) round($x, self::PRECISION));
                $this->xml_writer->write_attribute('y1', (string) round($y, self::PRECISION));
                $this->xml_writer->write_attribute('x2', (string) round($x, self::PRECISION));
                $this->xml_writer->write_attribute('y2', (string) round($y + $height, self::PRECISION));
                break;
            case Gradient_Type::DIAGONAL():
                $this->xml_writer->write_attribute('x1', (string) round($x, self::PRECISION));
                $this->xml_writer->write_attribute('y1', (string) round($y, self::PRECISION));
                $this->xml_writer->write_attribute('x2', (string) round($x + $width, self::PRECISION));
                $this->xml_writer->write_attribute('y2', (string) round($y + $height, self::PRECISION));
                break;
            case Gradient_Type::INVERSE_DIAGONAL():
                $this->xml_writer->write_attribute('x1', (string) round($x, self::PRECISION));
                $this->xml_writer->write_attribute('y1', (string) round($y + $height, self::PRECISION));
                $this->xml_writer->write_attribute('x2', (string) round($x + $width, self::PRECISION));
                $this->xml_writer->write_attribute('y2', (string) round($y, self::PRECISION));
                break;
            case Gradient_Type::RADIAL():
                $this->xml_writer->write_attribute('cx', (string) round(($x + $width) / 2, self::PRECISION));
                $this->xml_writer->write_attribute('cy', (string) round(($y + $height) / 2, self::PRECISION));
                $this->xml_writer->write_attribute('r', (string) round(max($width, $height) / 2, self::PRECISION));
                break;
        }
        $to_be_hashed = $this->get_color_string($start_color) . $this->get_color_string($end_color) . $gradient->get_type();
        if ($start_color instanceof Alpha) {
            $to_be_hashed .= (string) $start_color->get_alpha();
        }
        $id = sprintf('g%d-%s', ++$this->gradient_count, hash('xxh64', $to_be_hashed));
        $this->xml_writer->write_attribute('id', $id);
        $this->xml_writer->start_element('stop');
        $this->xml_writer->write_attribute('offset', '0%');
        $this->xml_writer->write_attribute('stop-color', $this->get_color_string($start_color));
        if ($start_color instanceof Alpha) {
            $this->xml_writer->write_attribute('stop-opacity', (string) $start_color->get_alpha());
        }
        $this->xml_writer->end_element();
        $this->xml_writer->start_element('stop');
        $this->xml_writer->write_attribute('offset', '100%');
        $this->xml_writer->write_attribute('stop-color', $this->get_color_string($end_color));
        if ($end_color instanceof Alpha) {
            $this->xml_writer->write_attribute('stop-opacity', (string) $end_color->get_alpha());
        }
        $this->xml_writer->end_element();
        $this->xml_writer->end_element();
        $this->xml_writer->end_element();
        return $id;
    }
    private function get_color_string(Color_Interface $color): string
    {
        $color = $color->to_rgb();
        return sprintf('#%02x%02x%02x', $color->get_red(), $color->get_green(), $color->get_blue());
    }
}