<?php

// Quick chart creation
$chart = \Core\Chart\ChartBuilder::quickBar(['A', 'B', 'C'], [10, 20, 15], 'Sales');

// Advanced configuration
$chart = \Core\Chart\ChartBuilder::line()
    ->data($complexData)
    ->config(\Core\Chart\Styles\ChartThemes::dark())
    ->smooth()
    ->showPoints()
    ->style('custom css')
    ->script('interactive js')
    ->render();

// Factory pattern with DI
$factory = $di->get('chartFactory');
$chart = $factory->create($config);