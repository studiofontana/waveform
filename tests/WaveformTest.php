<?php namespace Tests;

use Hudebnibanka\Waveform\PeekConverter;
use Hudebnibanka\Waveform\WaveformSVGGeneratorPolygon;
use Hudebnibanka\Waveform\WaveGenerator;
use PHPUnit\Framework\TestCase;

class WaveformTest extends TestCase
{
    public function testMp3ToSvgGeneration()
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

        file_put_contents(__DIR__ . "/results/peeks.json", $jsonPeeks);
        file_put_contents(__DIR__ . "/results/waveform.svg", $svg);
    }

    public function getWaveformTests(): array
    {
        return [
            ['1sec-BBBSD30A_22'],
            ['15sec-SATVCD140#34'],
            ['empty'],
            ['long-AMPD7_1'],
            ['gaps-GID68#35'],
        ];
    }

    /**
     * @dataProvider getWaveformTests
     */
    public function testVariousSvgGeneration($file): void
    {
        $waveform  = json_decode(file_get_contents(__DIR__ . "/fixtures/waveform-$file.json"), true);
        $generator = new WaveformSVGGeneratorPolygon();
        $svg = $generator->generateSVG($waveform);
        $this->assertSame(file_get_contents(__DIR__ . "/fixtures/result-$file.svg"), $svg);
    }
}