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
        switch ($SwitchingTime) {
            // Abort
            case 0:
                return;
                break;

            // Switching time one
            case 1:
                $switchingTimeName = 'Schaltzeit 1';
                $settings = json_decode($this->ReadPropertyString('SwitchingTimeOne'), true)[0];
                break;

            // Switching time two
            case 2:
                $switchingTimeName = 'Schaltzeit 2';
                $settings = json_decode($this->ReadPropertyString('SwitchingTimeTwo'), true)[0];
                break;

            // Switching time three
            case 3:
                $switchingTimeName = 'Schaltzeit 3';
                $settings = json_decode($this->ReadPropertyString('SwitchingTimeThree'), true)[0];
                break;

            // Switching time four
            case 4:
                $switchingTimeName = 'Schaltzeit 4';
                $settings = json_decode($this->ReadPropertyString('SwitchingTimeFour'), true)[0];
                break;

        }
        if (isset($settings) && isset($switchingTimeName)) {
            if (!$settings['UseSettings']) {
                $this->SendDebug(__FUNCTION__, 'Abbruch, die Variable ' . $switchingTimeName . ' ist deaktiviert!', 0);
                return;
            }
            $this->SendDebug(__FUNCTION__, 'Die ' . $switchingTimeName . ' wird ausgeführt!', 0);
            // Check conditions
            $conditions = [
                ['type' => 0, 'condition' => $settings['CheckAutomaticMode']],
                ['type' => 1, 'condition' => $settings['CheckLight']],
                ['type' => 2, 'condition' => $settings['CheckIsDay']],
                ['type' => 3, 'condition' => $settings['CheckTwilight']],
                ['type' => 4, 'condition' => $settings['CheckPresence']]];
            $checkConditions = $this->CheckConditions(json_encode($conditions));
            if (!$checkConditions) {
                $this->SetSwitchingTimes();
                return;
            }
            // Trigger action
            $this->TriggerExecutionDelay(intval($settings['ExecutionDelay']));
            $brightness = intval($settings['Brightness']);
            $dutyCycle = intval($settings['DutyCycle']);
            $dutyCycleUnit = intval($settings['DutyCycleUnit']);
            $this->SwitchLight($brightness, $dutyCycle, $dutyCycleUnit);
            $this->SetSwitchingTimes();
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
        $switchingTime = json_decode($this->ReadPropertyString('SwitchingTimeOne'));
        if (!empty($switchingTime)) {
            foreach ($switchingTime as $parameter) {
                if ($parameter->UseSettings) {
                    $interval = $this->GetSwitchingTimerInterval('SwitchingTimeOne');
                }
            }
        }
        $this->SetTimerInterval('SwitchingTimeOne', $interval);
        // Switching time two
        $interval = 0;
        $switchingTime = json_decode($this->ReadPropertyString('SwitchingTimeTwo'));
        if (!empty($switchingTime)) {
            foreach ($switchingTime as $parameter) {
                if ($parameter->UseSettings) {
                    $interval = $this->GetSwitchingTimerInterval('SwitchingTimeTwo');
                }
            }
        }
        $this->SetTimerInterval('SwitchingTimeTwo', $interval);
        // Switching time three
        $interval = 0;
        $switchingTime = json_decode($this->ReadPropertyString('SwitchingTimeThree'));
        if (!empty($switchingTime)) {
            foreach ($switchingTime as $parameter) {
                if ($parameter->UseSettings) {
                    $interval = $this->GetSwitchingTimerInterval('SwitchingTimeThree');
                }
            }
        }
        $this->SetTimerInterval('SwitchingTimeThree', $interval);
        // Switching time four
        $interval = 0;
        $switchingTime = json_decode($this->ReadPropertyString('SwitchingTimeFour'));
        if (!empty($switchingTime)) {
            foreach ($switchingTime as $parameter) {
                if ($parameter->UseSettings) {
                    $interval = $this->GetSwitchingTimerInterval('SwitchingTimeFour');
                }
            }
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
        $interval = 0;
        $switchingTime = json_decode($this->ReadPropertyString($TimerName), true);
        if (!empty($switchingTime)) {
            $now = time();
            $timer = json_decode($switchingTime[0]['SwitchingTime']);
            $hour = $timer->hour;
            $minute = $timer->minute;
            $second = $timer->second;
            $definedTime = $hour . ':' . $minute . ':' . $second;
            if (time() >= strtotime($definedTime)) {
                $timestamp = mktime($hour, $minute, $second, (int) date('n'), (int) date('j') + 1, (int) date('Y'));
            } else {
                $timestamp = mktime($hour, $minute, $second, (int) date('n'), (int) date('j'), (int) date('Y'));
            }
            $interval = ($timestamp - $now) * 1000;
        }
        return $interval;
    }

    /**
     * Sets the info for the next switching time.
     */
    private function SetNextSwitchingTimeInfo(): void
    {
        $timer = [];
        // Switching time one
        $switchingTime = json_decode($this->ReadPropertyString('SwitchingTimeOne'), true);
        if (!empty($switchingTime)) {
            if ($switchingTime[0]['UseSettings']) {
                $timer[] = ['name' => 'SwitchingTimeOne', 'interval' => $this->GetSwitchingTimerInterval('SwitchingTimeOne')];
            }
        }
        // Switching time two
        $switchingTime = json_decode($this->ReadPropertyString('SwitchingTimeTwo'), true);
        if (!empty($switchingTime)) {
            if ($switchingTime[0]['UseSettings']) {
                $timer[] = ['name' => 'SwitchingTimeTwo', 'interval' => $this->GetSwitchingTimerInterval('SwitchingTimeTwo')];
            }
        }
        // Switching time three
        $switchingTime = json_decode($this->ReadPropertyString('SwitchingTimeThree'), true);
        if (!empty($switchingTime)) {
            if ($switchingTime[0]['UseSettings']) {
                $timer[] = ['name' => 'SwitchingTimeThree', 'interval' => $this->GetSwitchingTimerInterval('SwitchingTimeThree')];
            }
        }
        // Switching time four
        $switchingTime = json_decode($this->ReadPropertyString('SwitchingTimeFour'), true);
        if (!empty($switchingTime)) {
            if ($switchingTime[0]['UseSettings']) {
                $timer[] = ['name' => 'SwitchingTimeFour', 'interval' => $this->GetSwitchingTimerInterval('SwitchingTimeFour')];
            }
        }
        if (!empty($timer)) {
            foreach ($timer as $key => $row) {
                $interval[$key] = $row['interval'];
            }
            array_multisort($interval, SORT_ASC, $timer);
            $timestamp = time() + ($timer[0]['interval'] / 1000);
            $this->SetValue('NextSwitchingTimeInfo', date('d.m.Y, H:i:s', ($timestamp)));
        } else {
            $this->SetValue('NextSwitchingTimeInfo', '-');
        }
    }
}