<?php namespace Hudebnibanka\Waveform;

class WaveformSVGGeneratorPolygon
{
    const ZERO_HEIGHT = 0.2;
    private $bottomPoints = [];
    private $topPoints = [];
    /**
     * @var float|int
     */
    private $oneStepWidth;

    public function generateSVG(array $waveform): string
    {
        $result = "<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"100%\" width=\"100%\" viewBox=\"0 0 100 100\" preserveAspectRatio=\"none\" fill=\"#38B3E7\">";
        $points = $this->getPoints($waveform);
        $points = implode(' ', $points);
        $result .= "<polygon points=\"$points\"/>";
        $result .= '</svg>';
        return $result;
    }

    private function getPoints(array $waveform): array
    {
        $this->oneStepWidth = $this->getOneStepWidth($waveform);
        $peeks              = $this->mergeRepeatedPeeks($waveform);
        $step               = 0;
        $x                  = 100;
        $peekWidth          = 0;
        $this->topPoints    = [];
        $this->bottomPoints = [];

        foreach ($peeks as $data) {
            list($peek, $repeatedPeekCount) = $data;
            $peekWidth = $this->getPeekWidth($repeatedPeekCount);
            if ($peek == 0) {
                $this->add2ZeroHeightPoints($step, $peekWidth);
                $step += $repeatedPeekCount;
                continue;
            }
            $peekHeight = $this->getPeekHeight($peek);
            $x          = $this->getCenterX($step, $peekWidth);
            $this->addPoint($x, $peekHeight);
            $step += $repeatedPeekCount;
        }

        $lastPeekWidth = $peekWidth;
        $lastX         = $this->round($x + $lastPeekWidth);

        $topZeroY    = 50 - self::ZERO_HEIGHT;
        $bottomZeroY = 50 + self::ZERO_HEIGHT;

        return array_merge(
            ['0,50', "0,$topZeroY"],
            $this->formatTopPoints(),
            ["$lastX,$topZeroY", "100,$topZeroY", "100,$bottomZeroY", "$lastX,$bottomZeroY"],
            $this->formatBottomPoints(),
            ["0,$bottomZeroY"]
        );
    }

    private function formatBottomPoints(): array
    {
        return $this->formatPoints(array_reverse($this->bottomPoints));
    }

    private function formatTopPoints(): array
    {
        return $this->formatPoints($this->topPoints);
    }

    private function formatPoints(array $points): array
    {
        return array_map(function (array $xy) {
            list($x, $y) = $xy;
            $x = $this->round($x);
            return "$x,$y";
        }, $points);
    }

    private function round(float $coordinate): float
    {
        return round($coordinate, 2);
    }

    private function addPoint(float $x, float $height)
    {
        $bottomY              = 50 + $height;
        $this->bottomPoints[] = [$x, $bottomY];
        $topY                 = 50 - $height;
        $this->topPoints[]    = [$x, $topY];
    }

    /**
     * @return float|int
     */
    private function getOneStepWidth(array $waveform)
    {
        $totalPeeks = count($waveform);
        return 100 / $totalPeeks;
    }

    public function mergeRepeatedPeeks(array $waveform): array
    {
        $result  = [];
        $control = $this->shiftPeek($waveform);
        $group   = [$control];
        while ($waveform) {
            $peek = $this->shiftPeek($waveform);
            if ($this->isContinousPeek($control, $peek)) {
                $group[] = $peek;
                continue;
            }

            $lastPeek = $group[0];
            if (in_array(0, $group)) {
                $lastPeek = 0;
            }
            $peekCount = count($group);
            $control   = $peek;
            $group     = [$control];
            $result[]  = [$lastPeek, $peekCount];
        }

        return $result;
    }

    private function shiftPeek(&$waveform): int
    {
        return (int)(array_shift($waveform) * 100);
    }

    /**
     * @param int $peek max is 100
     */
    private function getPeekHeight(int $peek): float
    {
        return $peek / 2;
    }

    private function getPeekWidth(int $repeatedPeekCount)
    {
        return $this->oneStepWidth * $repeatedPeekCount;
    }

    /**
     * @return float|int
     */
    private function getCenterX(int $step, float $width)
    {
        $fromX = $this->getFromX($step);
        $toX   = $fromX + $width;
        return ($toX + $fromX) / 2;
    }

    private function getFromX(int $step): float
    {
        return $step * $this->oneStepWidth;
    }

    /**
     * @param int $control
     * @param int $peek
     * @return bool
     */
    protected function isContinousPeek(int $control, int $peek): bool
    {
        return $control == $peek
            || ($control <= 1 && $peek <= 1); // flatten the mix of 1 and 0 peeks
    }

    private function add2ZeroHeightPoints(int $step, float $peekWidth): void
    {
        $peekHeight = self::ZERO_HEIGHT;
        $fromX      = $this->getFromX($step);
        $toX        = $fromX + $peekWidth;
        $this->addPoint($fromX, $peekHeight);
        $this->addPoint($toX, $peekHeight);
    }

}