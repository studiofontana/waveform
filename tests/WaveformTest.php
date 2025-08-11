<?php namespace Tests;

use Hudebnibanka\Waveform\PeekConverter;
use Hudebnibanka\Waveform\WaveformSVGGeneratorPolygon;
use Hudebnibanka\Waveform\WaveGenerator;
use PHPUnit\Framework\TestCase;

class WaveformTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $tmp = __DIR__ . '/../temp';
        if (!is_dir($tmp)) {
            mkdir($tmp, 0777, true);
        }
    }
    public function testMp3ToSvgGeneration()
    {
        $generator     = new WaveGenerator(__DIR__ . '/../temp');
        $converter     = new PeekConverter();
        $testedMp3     = __DIR__ . '/fixtures/from-mp3/INAMM9_1-After_Dark.mp3';
        $waveform      = $generator->generateWaves($testedMp3);
        $peeks         = $converter->wavesToScaledPeeks($waveform);
        $jsonPeeks     = json_encode($peeks);
        $expectedPeeks = file_get_contents(__DIR__ . '/fixtures/from-mp3/expectedPeeks.json');
        $this->assertSame($expectedPeeks, $jsonPeeks);

        $svgGenerator = new WaveformSVGGeneratorPolygon();
        $svg          = $svgGenerator->generateSVG($peeks);
        $expectedSvg  = file_get_contents(__DIR__ . '/fixtures/from-mp3/expectedWaveform.svg');
        $this->assertSame($expectedSvg, $svg);
    }

    public function testWrongFormatPeekGeneration()
    {
        $generator     = new WaveGenerator(__DIR__ . '/../temp');
        $converter     = new PeekConverter();
        $testedMp3     = __DIR__ . '/fixtures/from-wrong-format/actualy-mp4.mp3';
        $waveform      = $generator->generateWaves($testedMp3);
        $peeks         = $converter->wavesToScaledPeeks($waveform);
        $this->assertSame([0], $peeks);
    }

    public function getWaveformTests(): array
    {
        return [
            ['1sec-BBBSD30A_22'],
            ['15sec-SATVCD140#34'],
            ['empty'],
            ['long-empty', 'empty'],
            ['long-AMPD7_1'],
            ['gaps-GID68#35'],
        ];
    }

    /**
     * @dataProvider getWaveformTests
     */
    public function testVariousSvgGeneration($file, ?string $result = null): void
    {
        if (!$result) {
            $result = $file;
        }
        $waveform  = json_decode(file_get_contents(__DIR__ . "/fixtures/detailed-svg/waveform-$file.json"), true);
        $generator = new WaveformSVGGeneratorPolygon();
        $svg = $generator->generateSVG($waveform);
        $this->assertSame(file_get_contents(__DIR__ . "/fixtures/detailed-svg/result-$result.svg"), $svg);
    }
}