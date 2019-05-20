<?php

// Declare
declare(strict_types=1);

trait LS_timer
{
    protected function SetNextTimer()
    {
        // Disable timer first
        $this->SetTimerInterval('SwitchLightsOn', 0);
        $this->SetTimerInterval('SwitchLightsOff', 0);
        // Check automatic mode
        if (!$this->GetValue('AutomaticMode')) {
            return;
        }
        // Check lights
        $lights = json_decode($this->ReadPropertyString('Lights'));
        if (empty($lights)) {
            return;
        }
        $now = time();
        $timestamps = [];
        // Astro switch on
        $useSwitchOnAstro = $this->ReadPropertyBoolean('UseSwitchOnAstro');
        $switchOnAstro = $this->ReadPropertyInteger('SwitchOnAstro');
        if ($useSwitchOnAstro && $switchOnAstro != 0) {
            $timestamp = GetValueInteger($switchOnAstro);
            if ($timestamp > $now) {
                $interval = ($timestamp - $now) * 1000;
                $timestamps[] = ['timer' => 'SwitchOnAstro', 'timestamp' => $timestamp, 'interval' => $interval];
            }
        }
        // Astro switch off
        $useSwitchOffAstro = $this->ReadPropertyBoolean('UseSwitchOffAstro');
        $switchOffAstro = $this->ReadPropertyInteger('SwitchOffAstro');
        if ($useSwitchOffAstro && $switchOffAstro != 0) {
            $timestamp = GetValueInteger($switchOffAstro);
            if ($timestamp > $now) {
                $interval = ($timestamp - $now) * 1000;
                $timestamps[] = ['timer' => 'SwitchOffAstro', 'timestamp' => $timestamp, 'interval' => $interval];
            }
        }
        // Time switch on
        $useSwitchOnTime = $this->ReadPropertyBoolean('UseSwitchOnTime');
        $switchOnTime = $this->ReadPropertyString('SwitchOnTime');
        if ($useSwitchOnTime && !empty($switchOnTime)) {
            $switchOnTime = json_decode($this->ReadPropertyString('SwitchOnTime'));
            $hour = (integer)$switchOnTime->hour;
            $minute = (integer)$switchOnTime->minute;
            $second = (integer)$switchOnTime->second;
            $definedTime = $hour . ':' . $minute . ':' . $second;
            if (time() >= strtotime($definedTime)) {
                $timestamp = mktime($hour, $minute, $second, (integer)date('n'), (integer)date('j') + 1, (integer)date('Y'));
            } else {
                $timestamp = mktime($hour, $minute, $second, (integer)date('n'), (integer)date('j'), (integer)date('Y'));
            }
            if ($this->ReadPropertyBoolean('UseRandomSwitchOnDelay')) {
                $switchOnDelay = $this->ReadPropertyInteger('SwitchOnDelay');
                if ($switchOnDelay > 0) {
                    $delay = rand(0, $switchOnDelay * 60000) * 2 - $switchOnDelay * 60000;
                    $timestamp = $timestamp + $delay;
                }
            }
            if ($timestamp > $now) {
                $interval = ($timestamp - $now) * 1000;
                $timestamps[] = ['timer' => 'SwitchOnTime', 'timestamp' => $timestamp, 'interval' => $interval];
            }
        }
        // Time switch off
        $useSwitchOffTime = $this->ReadPropertyBoolean('UseSwitchOffTime');
        $switchOffTime = $this->ReadPropertyString('SwitchOffTime');
        if ($useSwitchOffTime && !empty($switchOffTime)) {
            $switchOffTime = json_decode($this->ReadPropertyString('SwitchOffTime'));
            $hour = (integer)$switchOffTime->hour;
            $minute = (integer)$switchOffTime->minute;
            $second = (integer)$switchOffTime->second;
            $definedTime = $hour . ':' . $minute . ':' . $second;
            if (time() >= strtotime($definedTime)) {
                $timestamp = mktime($hour, $minute, $second, (integer)date('n'), (integer)date('j') + 1, (integer)date('Y'));
            } else {
                $timestamp = mktime($hour, $minute, $second, (integer)date('n'), (integer)date('j'), (integer)date('Y'));
            }
            if ($this->ReadPropertyBoolean('UseRandomSwitchOffDelay')) {
                $switchOffDelay = $this->ReadPropertyInteger('SwitchOffDelay');
                if ($switchOffDelay > 0) {
                    $delay = rand(0, $switchOffDelay * 60000) * 2 - $switchOffDelay * 60000;
                    $timestamp = $timestamp + $delay;
                }
            }
            if ($timestamp > $now) {
                $interval = ($timestamp - $now) * 1000;
                $timestamps[] = ['timer' => 'SwitchOffTime', 'timestamp' => $timestamp, 'interval' => $interval];
            }
        }
        if (empty($timestamps)) {
            return;
        }
        $this->SendDebug('NextTimer', json_encode($timestamps), 0);
        // Get next timer interval
        $interval = array_column($timestamps, 'interval');
        $min = min($interval);
        $key = array_search($min, $interval);
        $timerMode = $timestamps[$key]['timer'];
        switch ($timerMode) {
            case 'SwitchOnAstro':
            case 'SwitchOnTime':
                $this->SetTimerInterval('SwitchLightsOn', $timestamps[$key]['interval']);
                $this->SetTimerInterval('SwitchLightsOff', 0);
                $timestamp = $timestamps[$key]['timestamp'];
                $timerInfo = $timestamp + date('Z');
                $date = gmdate('d.m.Y, H:i:s', (integer)$timerInfo);
                $unixTimestamp = strtotime($date);
                $day = date("l", $unixTimestamp);
                switch ($day) {
                    case 'Monday':
                        $day = 'Montag';
                        break;
                    case 'Tuesday':
                        $day = 'Dienstag';
                        break;
                    case 'Wednesday':
                        $day = 'Mittwoch';
                        break;
                    case 'Thursday':
                        $day = 'Donnerstag';
                        break;
                    case 'Friday':
                        $day = 'Freitag';
                        break;
                    case 'Saturday':
                        $day = 'Samstag';
                        break;
                    case 'Sunday':
                        $day = 'Sonntag';
                        break;
                }
                $date = $day . ', ' . $date;
                $this->SetValue('NextSwitchOnTime', $date);
                $this->SetValue('NextSwitchOffTime', '');
                break;
            case 'SwitchOffAstro':
            case 'SwitchOffTime':
                $this->SetTimerInterval('SwitchLightsOff', $timestamps[$key]['interval']);
                $this->SetTimerInterval('SwitchLightsOn', 0);
                $timestamp = $timestamps[$key]['timestamp'];
                $timerInfo = $timestamp + date('Z');
                $date = gmdate('d.m.Y, H:i:s', (integer)$timerInfo);
                $unixTimestamp = strtotime($date);
                $day = date("l", $unixTimestamp);
                switch ($day) {
                    case 'Monday':
                        $day = 'Montag';
                        break;
                    case 'Tuesday':
                        $day = 'Dienstag';
                        break;
                    case 'Wednesday':
                        $day = 'Mittwoch';
                        break;
                    case 'Thursday':
                        $day = 'Donnerstag';
                        break;
                    case 'Friday':
                        $day = 'Freitag';
                        break;
                    case 'Saturday':
                        $day = 'Samstag';
                        break;
                    case 'Sunday':
                        $day = 'Sonntag';
                        break;
                        break;
                }
                $date = $day . ', ' . $date;
                $this->SetValue('NextSwitchOnTime', '');
                $this->SetValue('NextSwitchOffTime', $date);
        }
    }
}