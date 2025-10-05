<?php
// app/_Core/Utils/SimplePdfGenerator.php
namespace Core\Utils;

class SimplePdfGenerator
{
    private string $content = '';
    private int $y = 720; // Starting Y position (top of page, in points)
    private int $page = 1;
    private array $colWidths = [];
    private int $pageWidth = 595; // A4 width in points
    private int $pageHeight = 842; // A4 height in points
    private int $margin = 50; // Left/right margin

    public function addPage(string $title): void
    {
        $this->content .= "%PDF-1.4\n";
        $this->content .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $this->content .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $this->content .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 4 0 R >> >> /MediaBox [0 0 {$this->pageWidth} {$this->pageHeight}] /Contents 5 0 R >>\nendobj\n";
        $this->content .= "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
        $this->content .= "5 0 obj\n<< /Length 6 0 R >>\nstream\n";
        $this->content .= "BT /F1 14 Tf {$this->margin} {$this->y} Td (" . addslashes($title) . ") Tj ET\n";
        $this->y -= 20;
        $this->addHeader();
        $this->addFooter();
    }

    private function addHeader(): void
    {
        $this->content .= "BT /F1 10 Tf {$this->margin} 800 Td (Performance Metrics Report - Page {$this->page}) Tj ET\n";
        $this->content .= "0.5 w {$this->margin} 790 m {($this->pageWidth - $this->margin)} 790 l S\n"; // Horizontal line
    }

    private function addFooter(): void
    {
        $this->content .= "BT /F1 8 Tf {$this->margin} 30 Td (Generated on " . date('Y-m-d H:i:s') . ") Tj ET\n";
    }

    public function addTable(array $headers, array $rows): void
    {
        // Calculate column widths based on content
        $this->colWidths = $this->calculateColumnWidths($headers, $rows);
        $rowHeight = 15;
        $fontSize = 10;

        $this->content .= "BT /F1 {$fontSize} Tf\n";
        $x = $this->margin;

        // Draw headers
        $this->content .= "0.5 w\n"; // Line width for borders
        foreach ($headers as $i => $header) {
            $this->drawCell($x, $this->y, $this->colWidths[$i], $rowHeight, $header);
            $x += $this->colWidths[$i];
        }
        $this->y -= $rowHeight;
        $this->content .= "{$this->margin} {$this->y} m {($this->pageWidth - $this->margin)} {$this->y} l S\n"; // Horizontal line below headers

        // Draw rows
        foreach ($rows as $row) {
            if ($this->y < 50) {
                $this->startNewPage();
                $x = $this->margin;
                foreach ($headers as $i => $header) {
                    $this->drawCell($x, $this->y, $this->colWidths[$i], $rowHeight, $header);
                    $x += $this->colWidths[$i];
                }
                $this->y -= $rowHeight;
                $this->content .= "{$this->margin} {$this->y} m {($this->pageWidth - $this->margin)} {$this->y} l S\n";
            }
            $x = $this->margin;
            foreach ($row as $i => $cell) {
                $this->drawCell($x, $this->y, $this->colWidths[$i], $rowHeight, $cell);
                $x += $this->colWidths[$i];
            }
            $this->y -= $rowHeight;
        }
        $this->content .= "ET\nendstream\nendobj\n";
        $this->content .= "6 0 obj\n" . strlen($this->content) . "\nendobj\n";
    }

    public function addBarChart(string $title, array $data, string $xLabel, string $yLabel, float $maxValue): void
    {
        if ($this->y < 300) { // Reserve space for chart (approx. 250 points)
            $this->startNewPage();
        }
        $this->content .= "BT /F1 12 Tf {$this->margin} {$this->y} Td (" . addslashes($title) . ") Tj ET\n";
        $this->y -= 20;

        $chartWidth = $this->pageWidth - 2 * $this->margin;
        $chartHeight = 200;
        $barWidth = $chartWidth / (count($data) * 2); // Bars take half the space, gaps take half
        $x = $this->margin;
        $yBase = $this->y - $chartHeight;

        // Draw axes
        $this->content .= "0.5 w\n";
        $this->content .= "{$this->margin} {$yBase} m {$this->margin} {$this->y} l S\n"; // Y-axis
        $this->content .= "{$this->margin} {$yBase} m " . ($this->pageWidth - $this->margin) . " {$yBase} l S\n"; // X-axis
        $this->content .= "BT /F1 8 Tf {$this->margin} " . ($this->y + 10) . " Td (" . addslashes($yLabel) . ") Tj ET\n";
        $this->content .= "BT /F1 8 Tf " . ($this->pageWidth - $this->margin - 20) . " " . ($yBase - 20) . " Td (" . addslashes($xLabel) . ") Tj ET\n";

        // Draw bars
        foreach ($data as $item) {
            $value = (float)$item['value'];
            $label = $item['label'];
            $barHeight = ($value / $maxValue) * $chartHeight;
            $this->content .= "0.7 0.7 0.7 rg\n"; // Gray fill for bars
            $this->content .= "{$x} {$yBase} m {$x} " . ($yBase + $barHeight) . " l " . ($x + $barWidth) . " " . ($yBase + $barHeight) . " l " . ($x + $barWidth) . " {$yBase} l f\n";
            $this->content .= "0 0 0 rg\n"; // Reset to black
            $this->content .= "BT /F1 8 Tf {$x} " . ($yBase - 10) . " Td (" . addslashes(substr($label, 0, 15)) . ") Tj ET\n";
            $x += $barWidth * 2; // Space between bars
        }

        $this->y -= $chartHeight + 30;
    }

    private function drawCell(float $x, float $y, float $width, float $height, string $text): void
    {
        // Draw cell border
        $this->content .= "{$x} {$y} m {($x + $width)} {$y} l {($x + $width)} " . ($y - $height) . " l {$x} " . ($y - $height) . " l {$x} {$y} l S\n";
        // Draw text (clipped to fit width)
        $text = substr($text, 0, floor($width / 5)); // Rough estimate: 5 points per character
        $this->content .= "{($x + 2)} " . ($y - $height + 3) . " Td (" . addslashes($text) . ") Tj\n";
    }

    private function calculateColumnWidths(array $headers, array $rows): array
    {
        $availableWidth = $this->pageWidth - 2 * $this->margin;
        $numColumns = count($headers);
        $baseWidth = $availableWidth / $numColumns;
        $widths = array_fill(0, $numColumns, $baseWidth);

        // Adjust widths based on content length
        $maxLengths = array_fill(0, $numColumns, 0);
        foreach (array_merge([$headers], $rows) as $row) {
            foreach ($row as $i => $cell) {
                $maxLengths[$i] = max($maxLengths[$i], strlen((string)$cell));
            }
        }

        $totalLength = array_sum($maxLengths);
        if ($totalLength > 0) {
            foreach ($maxLengths as $i => $length) {
                $widths[$i] = ($length / $totalLength) * $availableWidth;
                $widths[$i] = max(50, min($widths[$i], 150)); // Enforce min/max width
            }
        }

        // Normalize widths to fit page
        $totalWidth = array_sum($widths);
        if ($totalWidth > 0) {
            $scale = $availableWidth / $totalWidth;
            $widths = array_map(fn($w) => $w * $scale, $widths);
        }

        return $widths;
    }

    private function startNewPage(): void
    {
        $this->content .= "ET\nendstream\nendobj\n";
        $this->content .= "6 0 obj\n" . strlen($this->content) . "\nendobj\n";
        $this->y = 720;
        $this->page++;
        $this->content .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 4 0 R >> >> /MediaBox [0 0 {$this->pageWidth} {$this->pageHeight}] /Contents " . (5 + $this->page - 1) . " 0 R >>\nendobj\n";
        $this->content .= (5 + $this->page - 1) . " 0 obj\n<< /Length " . (6 + $this->page - 1) . " 0 R >>\nstream\nBT /F1 10 Tf\n";
        $this->addHeader();
        $this->addFooter();
    }

    public function output(): string
    {
        $this->content .= "xref\n0 " . (7 + $this->page - 1) . "\n0000000000 65535 f \n";
        $offset = 0;
        for ($i = 1; $i <= 6 + $this->page - 1; $i++) {
            $this->content .= sprintf("%010d 00000 n \n", $offset);
            $offset += strpos(substr($this->content, $offset), "endobj\n") + 7;
        }
        $this->content .= "trailer\n<< /Size " . (7 + $this->page - 1) . " /Root 1 0 R >>\nstartxref\n" . $offset . "\n%%EOF";
        return $this->content;
    }
}