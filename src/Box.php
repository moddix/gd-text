<?php

namespace GDText;

use GDText\Struct\Point;
use GDText\Struct\Rectangle;

class Box
{
    /**
     * @var resource
     */
    protected $im;

    /**
     * @var int
     */
    protected $strokeSize = 0;

    /**
     * @var Color
     */
    protected $strokeColor;
    /**
     * @var int
     */
    protected $letterSpacing;

    /**
     * @var int
     */
    protected $fontSize = 12;

    /**
     * @var Color
     */
    protected $fontColor;

    /**
     * @var string
     */
    protected $alignX = 'left';

    /**
     * @var string
     */
    protected $alignY = 'top';

    /**
     * @var int
     */
    protected $textWrapping = TextWrapping::WrapWithOverflow;

    /**
     * @var float
     */
    protected $lineHeight = 1.25;

    /**
     * @var float
     */
    protected $baseline = 0.2;

    /**
     * @var string
     */
    protected $fontFace = null;

    /**
     * @var bool
     */
    protected $debug = false;

    /**
     * @var bool|array
     */
    protected $textShadow = false;

    /**
     * @var bool|Color
     */
    protected $backgroundColor = false;

    /**
     * @var Rectangle
     */
    protected $box;

    public function __construct(&$image)
    {
        $this->im = $image;
        $this->fontColor = new Color(0, 0, 0);
        $this->strokeColor = new Color(0, 0, 0);
        $this->box = new Rectangle(0, 0, 100, 100);
    }

    /**
     * @param Color $color Font color
     */
    public function setFontColor(Color $color)
    {
        $this->fontColor = $color;
    }

    /**
     * @param string $path Path to the font file
     */
    public function setFontFace($path)
    {
        $this->fontFace = $path;
    }

    /**
     * @param int $v Font size in *pixels*
     */
    public function setFontSize($v)
    {
        $this->fontSize = $v;
    }

    /**
     * @param Color $color Stroke color
     */
    public function setStrokeColor(Color $color)
    {
        $this->strokeColor = $color;
    }

    /**
     * @param int $v Stroke size in *pixels*
     */
    public function setStrokeSize($v)
    {
        $this->strokeSize = $v;
    }

    /**
     * @param int $ls
     */
    public function setLetterSpacing($ls)
    {
        $this->letterSpacing = $ls;
    }

    /**
     * @param Color $color Shadow color
     * @param int $xShift Relative shadow position in pixels. Positive values move shadow to right, negative to left.
     * @param int $yShift Relative shadow position in pixels. Positive values move shadow to bottom, negative to up.
     */
    public function setTextShadow(Color $color, $xShift, $yShift)
    {
        $this->textShadow = array(
            'color' => $color,
            'offset' => new Point($xShift, $yShift)
        );
    }

    /**
     * @param Color $color Font color
     */
    public function setBackgroundColor(Color $color)
    {
        $this->backgroundColor = $color;
    }

    /**
     * Allows to customize spacing between lines.
     * @param float $v Height of the single text line, in percents, proportionally to font size
     */
    public function setLineHeight($v)
    {
        $this->lineHeight = $v;
    }

    /**
     * @param float $v Position of baseline, in percents, proportionally to line height measuring from the bottom.
     */
    public function setBaseline($v)
    {
        $this->baseline = $v;
    }

    /**
     * Sets text alignment inside text box
     * @param string $x Horizontal alignment. Allowed values are: left, center, right.
     * @param string $y Vertical alignment. Allowed values are: top, center, bottom.
     */
    public function setTextAlign($x = 'left', $y = 'top')
    {
        $xAllowed = array('left', 'right', 'center');
        $yAllowed = array('top', 'bottom', 'center');

        if (!in_array($x, $xAllowed)) {
            throw new \InvalidArgumentException('Invalid horizontal alignment value was specified.');
        }

        if (!in_array($y, $yAllowed)) {
            throw new \InvalidArgumentException('Invalid vertical alignment value was specified.');
        }

        $this->alignX = $x;
        $this->alignY = $y;
    }

    /**
     * Sets text box position and dimensions
     * @param int $x Distance in pixels from left edge of image.
     * @param int $y Distance in pixels from top edge of image.
     * @param int $width Width of text box in pixels.
     * @param int $height Height of text box in pixels.
     */
    public function setBox($x, $y, $width, $height)
    {
        $this->box = new Rectangle($x, $y, $width, $height);
    }

    /**
     * Enables debug mode. Whole text box and individual lines will be filled with random colors.
     */
    public function enableDebug()
    {
        $this->debug = true;
    }

    /**
     * @param int $textWrapping
     */
    public function setTextWrapping($textWrapping)
    {
        $this->textWrapping = $textWrapping;
    }

    /**
     * Draws the text on the picture.
     * @param string $text Text to draw. May contain newline characters.
     */
    public function draw($text)
    {
        if (!isset($this->fontFace)) {
            throw new \InvalidArgumentException('No path to font file has been specified.');
        }

        switch ($this->textWrapping) {
            case TextWrapping::NoWrap:
                $lines = array($text);
                break;
            case TextWrapping::WrapWithOverflow:
            default:
                $lines = $this->wrapTextWithOverflow($text);
                break;
        }

        if ($this->debug) {
            // Marks whole text box area with color
            $this->drawFilledRectangle(
                $this->box,
                new Color(rand(180, 255), rand(180, 255), rand(180, 255), 80)
            );
        }

        $lineHeightPx = $this->lineHeight * $this->fontSize;
        $textHeight = count($lines) * $lineHeightPx;

        switch ($this->alignY) {
            case VerticalAlignment::Center:
                $yAlign = ($this->box->getHeight() / 2) - ($textHeight / 2);
                break;
            case VerticalAlignment::Bottom:
                $yAlign = $this->box->getHeight() - $textHeight;
                break;
            case VerticalAlignment::Top:
            default:
                $yAlign = 0;
        }

        $n = 0;
        foreach ($lines as $line) {
            // calculate box with spacing
            if ($this->letterSpacing) {
                $box = $this->calculateBoxWithSpacing($line);
            } else {
                $box = $this->calculateBox($line);
            }

            switch ($this->alignX) {
                case HorizontalAlignment::Center:
                    $xAlign = ($this->box->getWidth() - $box->getWidth()) / 2;
                    break;
                case HorizontalAlignment::Right:
                    $xAlign = ($this->box->getWidth() - $box->getWidth());
                    break;
                case HorizontalAlignment::Left:
                default:
                    $xAlign = 0;
            }
            $yShift = $lineHeightPx * (1 - $this->baseline);

            // current line X and Y position
            $xMOD = $this->box->getX() + $xAlign;
            $yMOD = $this->box->getY() + $yAlign + $yShift + ($n * $lineHeightPx);

            if ($line && $this->backgroundColor) {
                // Marks whole text box area with given background-color
                $backgroundHeight = $this->fontSize;

                $this->drawFilledRectangle(
                    new Rectangle(
                        $xMOD,
                        $this->box->getY() + $yAlign + ($n * $lineHeightPx) + ($lineHeightPx - $backgroundHeight) + (1 - $this->lineHeight) * 13 * (1 / 50 * $this->fontSize),
                        $box->getWidth(),
                        $backgroundHeight
                    ),
                    $this->backgroundColor
                );
            }

            if ($this->debug) {
                // Marks current line with color
                $this->drawFilledRectangle(
                    new Rectangle(
                        $xMOD,
                        $this->box->getY() + $yAlign + ($n * $lineHeightPx),
                        $box->getWidth(),
                        $lineHeightPx
                    ),
                    new Color(rand(1, 180), rand(1, 180), rand(1, 180))
                );
            }

            if ($this->textShadow !== false) {
                $this->drawInternal(
                    new Point(
                        $xMOD + $this->textShadow['offset']->getX(),
                        $yMOD + $this->textShadow['offset']->getY()
                    ),
                    $this->textShadow['color'],
                    $line
                );
            }

            $this->strokeText($xMOD, $yMOD, $line);
            $this->drawInternalWithSpacing(
                new Point(
                    $xMOD,
                    $yMOD
                ),
                $this->fontColor,
                $line,
                $this->letterSpacing
            );

            $n++;
        }
    }

    /**
     * Splits overflowing text into array of strings.
     * @param string $text
     * @return string[]
     */
    protected function wrapTextWithOverflow($text)
    {
        $lines = array();
        // Split text explicitly into lines by \n, \r\n and \r
        $explicitLines = preg_split('/\n|\r\n?/', $text);
        foreach ($explicitLines as $line) {
            // Check every line if it needs to be wrapped
            $words = explode(" ", $line);
            $line = $words[0];
            for ($i = 1; $i < count($words); $i++) {
                $box = $this->calculateBox($line . " " . $words[$i]);
                if ($box->getWidth() >= $this->box->getWidth()) {
                    $lines[] = $line;
                    $line = $words[$i];
                } else {
                    $line .= " " . $words[$i];
                }
            }
            $lines[] = $line;
        }
        return $lines;
    }

    /**
     * @return float
     */
    protected function getFontSizeInPoints()
    {
        return 0.75 * $this->fontSize;
    }

    /**
     * @param Rectangle $rect
     * @param Color $color
     */
    protected function drawFilledRectangle(Rectangle $rect, Color $color)
    {
        imagefilledrectangle(
            $this->im,
            $rect->getLeft(),
            $rect->getTop(),
            $rect->getRight(),
            $rect->getBottom(),
            $color->getIndex($this->im)
        );
    }

    /**
     * Returns the bounding box of a text.
     * @param string $text
     * @return Rectangle
     */
    protected function calculateBox($text)
    {
        $bounds = imagettfbbox($this->getFontSizeInPoints(), 0, $this->fontFace, $text);

        $xLeft = $bounds[0]; // (lower|upper) left corner, X position
        $xRight = $bounds[2]; // (lower|upper) right corner, X position
        $yLower = $bounds[1]; // lower (left|right) corner, Y position
        $yUpper = $bounds[5]; // upper (left|right) corner, Y position

        return new Rectangle(
            $xLeft,
            $yUpper,
            $xRight - $xLeft,
            $yLower - $yUpper
        );
    }

    /**
     * @param $text
     * @return Rectangle
     */
    protected function calculateBoxWithSpacing($text)
    {
        // calculate box with full line of text
        $rect = $this->calculateBox($text);
        // reset width of the rectangle for recalculation
        $rect->setWidth(0);
        for ($i = 0; $i < strlen($text); $i++) {
            // calculate width of a single letter
            $box = $this->calculateBox($text[$i]);
            // add letter spacing to width of the letter and add it to the already calculated width
            $width = $rect->getWidth() + ($box->getWidth() + $this->letterSpacing);
            // set new width to the rectangle
            $rect->setWidth($width);
        }

        return $rect;
    }

    protected function strokeText($x, $y, $text)
    {
        $size = $this->strokeSize;
        if ($size <= 0) return;
        for ($c1 = $x - $size; $c1 <= $x + $size; $c1++) {
            for ($c2 = $y - $size; $c2 <= $y + $size; $c2++) {
                $this->drawInternal(new Point($c1, $c2), $this->strokeColor, $text);
            }
        }
    }

    /**
     * @param Point $position
     * @param Color $color
     * @param string $text
     * @param int $spacing
     */
    protected function drawInternalWithSpacing(Point $position, Color $color, $text, $spacing = 0)
    {
        if ($spacing == 0) {
            $this->drawInternal($position, $color, $text);
            return;
        }

        $temp_x = $position->getX();
        for ($i = 0; $i < strlen($text); $i++) {
            // set temporary X position
            $position->setX($temp_x);
            // draw single letter
            $boundingBox = $this->drawInternal($position, $color, $text[$i]);
            // calculate next X position
            $temp_x += $spacing + ($boundingBox[2] - $boundingBox[0]);
        }
    }

    /**
     * @param Point $position
     * @param Color $color
     * @param string $text
     *
     * @return array
     */
    protected function drawInternal(Point $position, Color $color, $text)
    {
        return imagettftext(
            $this->im,
            $this->getFontSizeInPoints(),
            0, // no rotation
            $position->getX(),
            $position->getY(),
            $color->getIndex($this->im),
            $this->fontFace,
            $text
        );
    }
}
