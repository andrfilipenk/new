<?php

namespace Core\Calendar\Models;

/**
 * Value object representing calendar dimensions
 */
class Dimensions
{
    public function __construct(
        private int $width,
        private int $height,
        private int $headerHeight = 60,
        private int $cellWidth = 120,
        private int $cellHeight = 80,
        private int $marginTop = 10,
        private int $marginRight = 10,
        private int $marginBottom = 10,
        private int $marginLeft = 10
    ) {
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getHeaderHeight(): int
    {
        return $this->headerHeight;
    }

    public function getCellWidth(): int
    {
        return $this->cellWidth;
    }

    public function getCellHeight(): int
    {
        return $this->cellHeight;
    }

    public function getMarginTop(): int
    {
        return $this->marginTop;
    }

    public function getMarginRight(): int
    {
        return $this->marginRight;
    }

    public function getMarginBottom(): int
    {
        return $this->marginBottom;
    }

    public function getMarginLeft(): int
    {
        return $this->marginLeft;
    }

    public function getContentWidth(): int
    {
        return $this->width - $this->marginLeft - $this->marginRight;
    }

    public function getContentHeight(): int
    {
        return $this->height - $this->marginTop - $this->marginBottom;
    }

    public function getBodyHeight(): int
    {
        return $this->getContentHeight() - $this->headerHeight;
    }

    public function toArray(): array
    {
        return [
            'width' => $this->width,
            'height' => $this->height,
            'headerHeight' => $this->headerHeight,
            'cellWidth' => $this->cellWidth,
            'cellHeight' => $this->cellHeight,
            'marginTop' => $this->marginTop,
            'marginRight' => $this->marginRight,
            'marginBottom' => $this->marginBottom,
            'marginLeft' => $this->marginLeft,
        ];
    }
}
