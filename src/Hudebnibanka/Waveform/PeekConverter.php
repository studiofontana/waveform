<?php namespace Hudebnibanka\Waveform;

class PeekConverter
{
    /**
     * Coverts waves to peek values from 0 to 1
     * @return float[]
     */
    public function wavesToScaledPeeks(array $waveform): array
    {
        if (!$waveform) {
            return [];
        }

        $peeks = $this->cutOfCenterWaveLine($waveform);
        return $this->scaleDownPeeks($peeks);
    }

    /**
     * @param float[] $waveform
     * @return float[]
     */
    private function cutOfCenterWaveLine(array $waveform): array
    {
        return array_map(function (float $wave) {
            $peek = ($wave / 128 - 1) * 10;
            return $peek < 0 ? $peek * -1 : $peek;
        }, $waveform);
    }

    /**
     * @param float[] $peeks
     * @return float[]
     */
    protected function scaleDownPeeks(array $peeks): array
    {
        $capModifier = 1 / max($peeks);
        $waveform    = [];
        foreach ($peeks as $point) {
            $peek = $point * $capModifier;
            if ($peek > 0) {
                $peek = round($peek, 2);
            }
            $waveform[] = $peek;
        }
        return $waveform;
    }
}