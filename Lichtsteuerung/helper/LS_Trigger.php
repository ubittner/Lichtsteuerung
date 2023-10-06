<?php

/**
 * @project       Lichtsteuerung/helper
 * @file          LS_Trigger.php
 * @author        Ulrich Bittner
 * @copyright     2023 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

declare(strict_types=1);

trait LS_Trigger
{
    /**
     * @param int $SenderID
     * @param bool $ValueChanged
     * false =  same value,
     * true =   value changed
     *
     * @return void
     * @throws Exception
     */
    public function CheckTriggerConditions(int $SenderID, bool $ValueChanged): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $this->SendDebug(__FUNCTION__, 'Sender: ' . $SenderID, 0);
        $valueChangedText = 'nicht ';
        if ($ValueChanged) {
            $valueChangedText = '';
        }
        $this->SendDebug(__FUNCTION__, 'Der Wert hat sich ' . $valueChangedText . 'geändert', 0);
        if ($this->CheckMaintenance()) {
            return;
        }
        $variables = json_decode($this->ReadPropertyString('Triggers'), true);
        foreach ($variables as $key => $variable) {
            if (!$variable['Use']) {
                continue;
            }
            if ($variable['PrimaryCondition'] != '') {
                $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                if (array_key_exists(0, $primaryCondition)) {
                    if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                        $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                        if ($SenderID == $id) {
                            $this->SendDebug(__FUNCTION__, 'Listenschlüssel: ' . $key, 0);
                            if (!$variable['UseMultipleAlerts'] && !$ValueChanged) {
                                $this->SendDebug(__FUNCTION__, 'Abbruch, die Mehrfachauslösung ist nicht aktiviert!', 0);
                                continue;
                            }
                            $execute = true;
                            //Check primary condition
                            if (!IPS_IsConditionPassing($variable['PrimaryCondition'])) {
                                $execute = false;
                            }
                            //Check secondary condition
                            if (!IPS_IsConditionPassing($variable['SecondaryCondition'])) {
                                $execute = false;
                            }
                            if (!$execute) {
                                $this->SendDebug(__FUNCTION__, 'Abbruch, die Auslösebedingungen wurden nicht erfüllt!', 0);
                            } else {
                                $this->SendDebug(__FUNCTION__, 'Die Auslösebedingungen wurden erfüllt.', 0);

                                //Check conditions
                                $this->SendDebug(__FUNCTION__, 'Settings: ' . json_encode($variable), 0);
                                $checkConditions = $this->CheckAllConditions(json_encode($variable));
                                $this->SendDebug(__FUNCTION__, 'Result checkConditions: ' . json_encode($checkConditions), 0);
                                if ($checkConditions) {
                                    $this->SendDebug(__FUNCTION__, 'Alle weiteren Bedingungen wurden erfüllt!', 0);
                                    //Check time
                                    $checkTime = $this->CheckTimeCondition($variable['ExecutionTimeAfter'], $variable['ExecutionTimeBefore']);
                                    if ($checkTime) {
                                        //Trigger action
                                        $this->TriggerExecutionDelay(intval($variable['ExecutionDelay']));
                                        $brightness = intval($variable['Brightness']);
                                        if ($variable['UpdateLastBrightness']) {
                                            $this->SetValue('LastBrightness', $brightness);
                                        }
                                        $dutyCycle = intval($variable['DutyCycle']);
                                        $dutyCycleUnit = intval($variable['DutyCycleUnit']);
                                        $this->SwitchLight($brightness, $dutyCycle, $dutyCycleUnit);
                                        return;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}