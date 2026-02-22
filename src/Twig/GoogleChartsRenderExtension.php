<?php

namespace App\Twig;

use CMEN\GoogleChartsBundle\GoogleCharts\Chart;
use CMEN\GoogleChartsBundle\Output\Javascript\ChartOutput;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class GoogleChartsRenderExtension extends AbstractExtension
{
    public function __construct(private readonly ChartOutput $chartOutput)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('piechart', fn (?Chart $chart): string => $this->renderChart($chart, 'pie'), ['is_safe' => ['html']]),
            new TwigFunction('barchart', fn (?Chart $chart): string => $this->renderChart($chart, 'bar'), ['is_safe' => ['html']]),
            new TwigFunction('linechart', fn (?Chart $chart): string => $this->renderChart($chart, 'line'), ['is_safe' => ['html']]),
        ];
    }

    private function renderChart(?Chart $chart, string $prefix): string
    {
        if ($chart === null) {
            return '';
        }

        $elementId = sprintf('chart_%s_%s', $prefix, substr(bin2hex(random_bytes(6)), 0, 12));

        return sprintf(
            '<div id="%s" style="height:320px;"></div><script type="text/javascript">%s</script>',
            $elementId,
            $this->chartOutput->fullCharts($chart, $elementId)
        );
    }
}
