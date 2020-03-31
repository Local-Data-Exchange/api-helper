<?php

namespace Lde\ApiHelper\Helpers;

use Prometheus\Exception\MetricNotFoundException;
use Prometheus\Exception\MetricsRegistrationException;
use App\Exceptions\ArraySizeMisMatchException;
use Illuminate\Support\Facades\Log;


class StatsHelper
{
    public static function incCounter($counter_name, $qty = 1, $description = null)
    {
        try {
            $labelNames = array();
            $labels = array();
            $exporter = app('prometheus');

            // Ensure labelNames have env
            if (! in_array('env', $labelNames)) {
                $labelNames[] = 'env';
            }

            // Add env to $labels
            if (! in_array(app()->environment(), $labels)) {
                $labels[] = app()->environment();
            }

            //Overridding prometheus config from api_helper
            $prometheusConfig = config('api_helper.prometheus');
            foreach($prometheusConfig as $key => $row) {
                $labelNames[] = $key;
                $labels[] = $row;
            }

            if (count($labels) != count($labelNames)) {
                throw new ArraySizeMisMatchException("{$counter_name} has mismatch labels and names");
            }

            try {
                $counter = $exporter->getCounter($counter_name);
            } catch (MetricNotFoundException $ex) {
                $counter = $exporter->registerCounter($counter_name, $description, $labelNames);
            } catch (MetricsRegistrationException $ex) {
                $counter = $exporter->registerCounter($counter_name, $description, $labelNames);
            }

            if ($counter->getLabelNames()) {
                if (! empty($labels)) {
                    $counter->incBy($qty, $labels);
                } else {
                    $counter->incBy($qty, ['none']);
                }
            } else {
                $counter->incBy($qty);
            }
        } catch (\Exception $ex) {
            Log::error("StatsHelper->incCounter() threw an exception!!", [
                'error'   => $ex->getMessage(),
                'name'    => $counter_name,
                'labels'  => $labels,
                'labelNames' => $labelNames,
            ]);
        }
    }

    public static function incHistogram($histogram_name, $qty = 1, Array $labels = [], $description = null, Array $labelNames = [])
    {
        try {
            $exporter = app('prometheus');

            // Ensure labelNames have env
            if (! in_array('env', $labelNames)) {
                $labelNames[] = 'env';
            }

            // Add env to $labels
            if (! in_array(app()->environment(), $labels)) {
                $labels[] = app()->environment();
            }

            //Overridding prometheus config from api_helper
            $prometheusConfig = config('api_helper.prometheus');
            foreach($prometheusConfig as $key => $row) {
                $labelNames[] = $key;
                $labels[] = $row;
            }

            if (count($labels) != count($labelNames)) {
                throw new ArraySizeMisMatchException("{$histogram_name} has mismatch labels and names");
            }

            try {
                $histogram = $exporter->getHistogram($histogram_name);
            } catch (MetricNotFoundException $ex) {
                $histogram = $exporter->registerHistogram($histogram_name, $description, $labelNames, [0.1, 0.25, 0.5, 0.75, 1.0, 2.5, 3.0, 3.5, 4.0, 4.5, 5.0, 7.5, 10.0]);
            } catch (MetricsRegistrationException $ex) {
                $histogram = $exporter->registerHistogram($histogram_name, $description, $labelNames, [0.1, 0.25, 0.5, 0.75, 1.0, 2.5, 3.0, 3.5, 4.0, 4.5, 5.0, 7.5, 10.0]);
            }

            if ($histogram->getLabelNames()) {
                if (! empty($labels)) {
                    $histogram->observe($qty, $labels);
                } else {
                    $histogram->observe($qty, ['none']);
                }
            } else {
                $histogram->observe($qty);
            }
        } catch (\Exception $ex) {
            Log::error("StatsHelper->incHistogram() threw an exception!!", [
                'error'   => $ex->getMessage(),
                'name'    => $histogram_name,
                'labels'  => $labels,
                'labelNames' => $labelNames,
            ]);
        }
    }
}
