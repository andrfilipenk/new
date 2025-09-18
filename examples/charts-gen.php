<?php

// Quick chart creation
$chart = ChartBuilder::quickBar(['A', 'B', 'C'], [10, 20, 15], 'Sales');

// Advanced configuration
$chart = ChartBuilder::line()
    ->data($complexData)
    ->config(ChartThemes::dark())
    ->smooth()
    ->showPoints()
    ->style('custom css')
    ->script('interactive js')
    ->render();

// Factory pattern with DI
$factory = $di->get('chartFactory');
$chart = $factory->create($config);