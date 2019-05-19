<?php

// Declare
declare(strict_types=1);

trait LS_timer
{
    //#################### Lights on

    /**
     *  Sets the timer for switching the lights on.
     */
    protected function SetSwitchLightsOnTimer()
    {
        $timerInterval = 0;
        $timerInfo = '';
        if ($this->GetValue('AutomaticMode')) {
            $now = time();
            if ($this->ReadPropertyBoolean('UseSwitchOnTime')) {
                // Check Astro
                $astro = $this->ReadPropertyInteger('SwitchOnAstro');
                if ($astro != 0) {
                    $timestamp = GetValueInteger($astro);
                    $timerInterval = ($timestamp - $now) * 1000;
                    $timerInfo = $timestamp + date('Z');
                } else {
                    // Timer
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
                    $timerInterval = ($timestamp - $now) * 1000;
                    $timerInfo = $timestamp + date('Z');
                }
                // Check random delay
                if ($this->ReadPropertyBoolean('UseRandomSwitchOnDelay')) {
                    $switchOnDelay = $this->ReadPropertyInteger('SwitchOnDelay');
                    if ($timerInterval != 0 && $switchOnDelay > 0) {
                        $delay = rand(0, $switchOnDelay * 60000) * 2 - $switchOnDelay * 60000;
                        $timerInterval = $timerInterval + $delay;
                        $timerInfo += $delay / 1000;
                    }
                }
            }
        }
        // Set timer
        $this->SetTimerInterval('SwitchLightsOn', $timerInterval);
        // Set next switch on info
        $date = '';
        if (!empty($timerInfo)) {
            $day = date('l', (integer)$timerInfo);
            switch ($day) {
                case 'Monday':
                    $day = 'Montag';
                    break;
                case 'Thuesday':
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
            $date = $day . ', ' . gmdate('d.m.Y, H:i:s', (integer)$timerInfo);
        }
        $this->SetValue('NextSwitchOnTime', $date);
    }

    //#################### Lights off

    /**
     * Sets the timer for switching the lights off.
     */
    protected function SetSwitchLightsOffTimer()
    {
        $timerInterval = 0;
        $timerInfo = '';
        if ($this->GetValue('AutomaticMode')) {
            $now = time();
            if ($this->ReadPropertyBoolean('UseSwitchOffTime')) {
                // Check Astro
                $astro = $this->ReadPropertyInteger('SwitchOffAstro');
                if ($astro != 0) {
                    $timestamp = GetValueInteger($astro);
                    $timerInterval = ($timestamp - $now) * 1000;
                    $timerInfo = $timestamp + date('Z');
                } else {
                    // Timer
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
                    $timerInterval = ($timestamp - $now) * 1000;
                    $timerInfo = $timestamp + date('Z');
                }
                // Check random delay
                if ($this->ReadPropertyBoolean('UseRandomSwitchOffDelay')) {
                    $switchOffDelay = $this->ReadPropertyInteger('SwitchOffDelay');
                    if ($timerInterval != 0 && $switchOffDelay > 0) {
                        $delay = rand(0, $switchOffDelay * 60000) * 2 - $switchOffDelay * 60000;
                        $timerInterval = $timerInterval + $delay;
                        $timerInfo += $delay / 1000;
                    }
                }
            }
        }
        // Set timer
        $this->SetTimerInterval('SwitchLightsOff', $timerInterval);
        // Set next switch off info
        $date = '';
        if (!empty($timerInfo)) {
            $day = date('l', (integer)$timerInfo);
            switch ($day) {
                case 'Monday':
                    $day = 'Montag';
                    break;
                case 'Thuesday':
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
            $date = $day . ', ' . gmdate('d.m.Y, H:i:s', (integer)$timerInfo);
        }
        $this->SetValue('NextSwitchOffTime', $date);
    }
}