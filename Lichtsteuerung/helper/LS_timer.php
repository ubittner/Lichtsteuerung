<?php

// Declare
declare(strict_types=1);

trait LS_timer
{
    protected function SetNextTimer()
    {
        // Check automatic mode
        if (!$this->GetValue('AutomaticMode')) {
            $this->SetTimerInterval('SwitchLightsOn', 0);
            $this->SetTimerInterval('SwitchLightsOff', 0);
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
            $this->SetTimerInterval('SwitchLightsOn', 0);
            $this->SetTimerInterval('SwitchLightsOff', 0);
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
                $timerInfo = $timestamps[$key]['timestamp'];
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
                $timerInfo = $timestamps[$key]['timestamp'];
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
                $this->SetValue('NextSwitchOnTime', $date);
                $this->SetValue('NextSwitchOffTime', '');
        }
    }


    //#################### Lights on

    /**
     *  Sets the timer for switching the lights on.

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
        }
        $this->SetValue('NextSwitchOnTime', $date);
    }

    //#################### Lights off

    /**
     * Sets the timer for switching the lights off.

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
        }
        $this->SetValue('NextSwitchOffTime', $date);
    }

    /**
     * Set the next timer for astro function.
     *
     * @param bool $State

    public function SetNextAstroTimer(bool $State)
    {
        // Switch on
        if ($State) {
            // Disable switch on timer
            $this->SetTimerInterval('SwitchLightsOn', 0);
            $this->SetValue('NextSwitchOnTime', '');
            // Set switch off timer
            if ($this->GetValue('AutomaticMode')) {
                $now = time();
                if ($this->ReadPropertyBoolean('UseSwitchOffTime')) {
                    $switchOffAstro = $this->ReadPropertyInteger('SwitchOffAstro');
                    if ($switchOffAstro != 0) {
                        $timestamp = GetValueInteger($switchOffAstro);
                        $timerInterval = ($timestamp - $now) * 1000;
                        $timerInfo = $timestamp + date('Z');
                        // Check random delay
                        if ($this->ReadPropertyBoolean('UseRandomSwitchOffDelay')) {
                            $switchOffDelay = $this->ReadPropertyInteger('SwitchOffDelay');
                            if ($timerInterval != 0 && $switchOffDelay > 0) {
                                $delay = rand(0, $switchOffDelay * 60000) * 2 - $switchOffDelay * 60000;
                                $timerInterval = $timerInterval + $delay;
                                $timerInfo += $delay / 1000;
                            }
                        }
                        // Set timer
                        $this->SetTimerInterval('SwitchLightsOff', $timerInterval);
                        // Set next switch off info
                        $date = '';
                        if (!empty($timerInfo)) {
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
                        }
                        $this->SetValue('NextSwitchOffTime', $date);
                    }
                }
            }
        }
        // Switch Off
        if (!$State) {
            // Disable switch off timer
            $this->SetTimerInterval('SwitchLightsOff', 0);
            $this->SetValue('NextSwitchOffTime', '');
            // Set switch on timer
            if ($this->GetValue('AutomaticMode')) {
                $now = time();
                if ($this->ReadPropertyBoolean('UseSwitchOnTime')) {
                    $switchOnAstro = $this->ReadPropertyInteger('SwitchOnAstro');
                    if ($switchOnAstro != 0) {
                        $timestamp = GetValueInteger($switchOnAstro);
                        $timerInterval = ($timestamp - $now) * 1000;
                        $timerInfo = $timestamp + date('Z');
                        // Check random delay
                        if ($this->ReadPropertyBoolean('UseRandomSwitchOnDelay')) {
                            $switchOnDelay = $this->ReadPropertyInteger('SwitchOnDelay');
                            if ($timerInterval != 0 && $switchOnDelay > 0) {
                                $delay = rand(0, $switchOnDelay * 60000) * 2 - $switchOnDelay * 60000;
                                $timerInterval = $timerInterval + $delay;
                                $timerInfo += $delay / 1000;
                            }
                        }
                        // Set timer
                        $this->SetTimerInterval('SwitchLightsOn', $timerInterval);
                        // Set next switch on info
                        $date = '';
                        if (!empty($timerInfo)) {
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
                        }
                        $this->SetValue('NextSwitchOnTime', $date);
                    }
                }
            }
        }
    }
    */
}