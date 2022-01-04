<?php namespace Tests;

use Hudebnibanka\Waveform\PeekConverter;
use Hudebnibanka\Waveform\WaveformSVGGeneratorPolygon;
use Hudebnibanka\Waveform\WaveGenerator;
use PHPUnit\Framework\TestCase;

class WaveformTest extends TestCase
{
    public function testGeneration()
    {
        $generator     = new WaveGenerator(__DIR__ . '/../temp');
        $converter     = new PeekConverter();
        $testedMp3     = __DIR__ . '/fixtures/INAMM9_1-After_Dark.mp3';
        $waveform      = $generator->generateWaves($testedMp3);
        $peeks         = $converter->wavesToScaledPeeks($waveform);
        $jsonPeeks     = json_encode($peeks);
        $expectedPeeks = file_get_contents(__DIR__ . '/fixtures/expectedPeeks.json');
        $this->assertSame($jsonPeeks, $expectedPeeks);

        $svgGenerator = new WaveformSVGGeneratorPolygon();
        $svg          = $svgGenerator->generateSVG($peeks);
        $expectedSvg  = file_get_contents(__DIR__ . '/fixtures/expectedWaveform.svg');
        $this->assertSame($svg, $expectedSvg);

        $timestamp = time();
        file_put_contents(__DIR__ . "/results/peeks-$timestamp.json", $jsonPeeks);
        file_put_contents(__DIR__ . "/results/waveform-$timestamp.svg", $svg);
    }
}