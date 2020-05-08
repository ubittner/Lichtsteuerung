<?php

// Declare
declare(strict_types=1);

trait LS_switchingTime
{
    /**
     * Executes a switching time.
     *
     * @param int $SwitchingTime
     */
    public function ExecuteSwitchingTime(int $SwitchingTime): void
    {
        $this->SendDebug(__FUNCTION__, 'Die Methode wird ausgeführt. (' . microtime(true) . ')', 0);
        if ($this->CheckMaintenanceMode()) {
            return;
        }
        switch ($SwitchingTime) {
            // Abort
            case 0:
                return;
                break;

            // Switching time one
            case 1:
                $switchingTimeName = 'Schaltzeit 1';
                $settings = json_decode($this->ReadPropertyString('SwitchingTimeOneActions'), true);
                break;

            // Switching time two
            case 2:
                $switchingTimeName = 'Schaltzeit 2';
                $settings = json_decode($this->ReadPropertyString('SwitchingTimeTwoActions'), true);
                break;

            // Switching time three
            case 3:
                $switchingTimeName = 'Schaltzeit 3';
                $settings = json_decode($this->ReadPropertyString('SwitchingTimeThreeActions'), true);
                break;

            // Switching time four
            case 4:
                $switchingTimeName = 'Schaltzeit 4';
                $settings = json_decode($this->ReadPropertyString('SwitchingTimeFourActions'), true);
                break;

        }
        if (isset($settings) && isset($switchingTimeName)) {
            $action = false;
            foreach ($settings as $setting) {
                if ($setting['UseSettings']) {
                    $action = true;
                }
            }
            if (!$action) {
                $this->SendDebug(__FUNCTION__, 'Abbruch, die ' . $switchingTimeName . ' hat keine aktivierte Aktion!', 0);
                return;
            }
            foreach ($settings as $setting) {
                if ($setting['UseSettings']) {
                    $this->SendDebug(__FUNCTION__, 'Die ' . $switchingTimeName . ' wird ausgeführt!', 0);
                    // Check conditions
                    $checkConditions = $this->CheckAllConditions(json_encode($setting));
                    if (!$checkConditions) {
                        $this->SetSwitchingTimes();
                        continue;
                    }
                    // Trigger action
                    $brightness = $setting['Brightness'];
                    if ($setting['UpdateLastBrightness']) {
                        $this->SetValue('LastBrightness', $brightness);
                    }
                    $this->TriggerExecutionDelay(intval($setting['ExecutionDelay']));
                    $this->SwitchLight($brightness, 0, 0);
                    $this->SetSwitchingTimes();
                }
            }
        }
    }

    //#################### Private

    /**
     * Registers the switching timers.
     */
    private function RegisterSwitchingTimers(): void
    {
        $this->RegisterTimer('SwitchingTimeOne', 0, 'LS_ExecuteSwitchingTime(' . $this->InstanceID . ', 1);');
        $this->RegisterTimer('SwitchingTimeTwo', 0, 'LS_ExecuteSwitchingTime(' . $this->InstanceID . ', 2);');
        $this->RegisterTimer('SwitchingTimeThree', 0, 'LS_ExecuteSwitchingTime(' . $this->InstanceID . ', 3);');
        $this->RegisterTimer('SwitchingTimeFour', 0, 'LS_ExecuteSwitchingTime(' . $this->InstanceID . ', 4);');
    }

    /**
     * Sets the switching times.
     */
    private function SetSwitchingTimes(): void
    {
        // Switching time one
        $interval = 0;
        $setTimer = false;
        $switchingTimeActions = json_decode($this->ReadPropertyString('SwitchingTimeOneActions'));
        if (!empty($switchingTimeActions)) {
            foreach ($switchingTimeActions as $switchingTimeAction) {
                if ($switchingTimeAction->UseSettings) {
                    $setTimer = true;
                }
            }
        }
        if ($setTimer) {
            $interval = $this->GetSwitchingTimerInterval('SwitchingTimeOne');
        }
        $this->SetTimerInterval('SwitchingTimeOne', $interval);
        // Switching time two
        $interval = 0;
        $setTimer = false;
        $switchingTimeActions = json_decode($this->ReadPropertyString('SwitchingTimeTwoActions'));
        if (!empty($switchingTimeActions)) {
            foreach ($switchingTimeActions as $switchingTimeAction) {
                if ($switchingTimeAction->UseSettings) {
                    $setTimer = true;
                }
            }
        }
        if ($setTimer) {
            $interval = $this->GetSwitchingTimerInterval('SwitchingTimeTwo');
        }
        $this->SetTimerInterval('SwitchingTimeTwo', $interval);
        // Switching time three
        $interval = 0;
        $setTimer = false;
        $switchingTimeActions = json_decode($this->ReadPropertyString('SwitchingTimeThreeActions'));
        if (!empty($switchingTimeActions)) {
            foreach ($switchingTimeActions as $switchingTimeAction) {
                if ($switchingTimeAction->UseSettings) {
                    $setTimer = true;
                }
            }
        }
        if ($setTimer) {
            $interval = $this->GetSwitchingTimerInterval('SwitchingTimeThree');
        }
        $this->SetTimerInterval('SwitchingTimeThree', $interval);
        // Switching time four
        $interval = 0;
        $setTimer = false;
        $switchingTimeActions = json_decode($this->ReadPropertyString('SwitchingTimeFourActions'));
        if (!empty($switchingTimeActions)) {
            foreach ($switchingTimeActions as $switchingTimeAction) {
                if ($switchingTimeAction->UseSettings) {
                    $setTimer = true;
                }
            }
        }
        if ($setTimer) {
            $interval = $this->GetSwitchingTimerInterval('SwitchingTimeFour');
        }
        $this->SetTimerInterval('SwitchingTimeFour', $interval);
        // Set info for next switching time
        $this->SetNextSwitchingTimeInfo();
    }

    /**
     * Gets the switching timer interval.
     *
     * @param string $TimerName
     * @return int
     */
    private function GetSwitchingTimerInterval(string $TimerName): int
    {
        $timer = json_decode($this->ReadPropertyString($TimerName));
        $now = time();
        $hour = $timer->hour;
        $minute = $timer->minute;
        $second = $timer->second;
        $definedTime = $hour . ':' . $minute . ':' . $second;
        if (time() >= strtotime($definedTime)) {
            $timestamp = mktime($hour, $minute, $second, (int) date('n'), (int) date('j') + 1, (int) date('Y'));
        } else {
            $timestamp = mktime($hour, $minute, $second, (int) date('n'), (int) date('j'), (int) date('Y'));
        }
        return ($timestamp - $now) * 1000;
    }

    /**
     * Sets the info for the next switching time.
     */
    private function SetNextSwitchingTimeInfo(): void
    {
        $timer = [];
        // Switching time one
        $switchingTimeActions = json_decode($this->ReadPropertyString('SwitchingTimeOneActions'), true);
        if (!empty($switchingTimeActions)) {
            foreach ($switchingTimeActions as $switchingTimeAction) {
                if ($switchingTimeAction['UseSettings']) {
                    $timer[] = ['name' => 'SwitchingTimeOne', 'interval' => $this->GetSwitchingTimerInterval('SwitchingTimeOne')];
                }
            }
        }
        // Switching time two
        $switchingTimeActions = json_decode($this->ReadPropertyString('SwitchingTimeTwoActions'), true);
        if (!empty($switchingTimeActions)) {
            foreach ($switchingTimeActions as $switchingTimeAction) {
                if ($switchingTimeAction['UseSettings']) {
                    $timer[] = ['name' => 'SwitchingTimeTwo', 'interval' => $this->GetSwitchingTimerInterval('SwitchingTimeTwo')];
                }
            }
        }
        // Switching time three
        $switchingTimeActions = json_decode($this->ReadPropertyString('SwitchingTimeThreeActions'), true);
        if (!empty($switchingTimeActions)) {
            foreach ($switchingTimeActions as $switchingTimeAction) {
                if ($switchingTimeAction['UseSettings']) {
                    $timer[] = ['name' => 'SwitchingTimeThree', 'interval' => $this->GetSwitchingTimerInterval('SwitchingTimeThree')];
                }
            }
        }
        // Switching time four
        $switchingTimeActions = json_decode($this->ReadPropertyString('SwitchingTimeFourActions'), true);
        if (!empty($switchingTimeActions)) {
            foreach ($switchingTimeActions as $switchingTimeAction) {
                if ($switchingTimeAction['UseSettings']) {
                    $timer[] = ['name' => 'SwitchingTimeFour', 'interval' => $this->GetSwitchingTimerInterval('SwitchingTimeFour')];
                }
            }
        }
        if (!empty($timer)) {
            foreach ($timer as $key => $row) {
                $interval[$key] = $row['interval'];
            }
            array_multisort($interval, SORT_ASC, $timer);
            $timestamp = time() + ($timer[0]['interval'] / 1000);
            $this->SetValue('NextSwitchingTime', $this->GetTimeStampString($timestamp));
        } else {
            $this->SetValue('NextSwitchingTime', '-');
        }
    }
}