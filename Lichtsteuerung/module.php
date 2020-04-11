<?php

/*
 * @module      Lichtsteuerung
 *
 * @prefix      LS
 *
 * @file        module.php
 *
 * @author      Ulrich Bittner
 * @copyright   (c) 2020
 * @license     CC BY-NC-SA 4.0
 *              https://creativecommons.org/licenses/by-nc-sa/4.0/
 *
 * @version     2.00-23
 * @date        2020-04-11, 18:00, 1586624400
 * @review      2020-04-11, 18:00
 *
 * @see         https://github.com/ubittnerLichtsteuerung
 *
 * @guids       Library
 *              {46195F9D-0325-41E2-B83B-A1192293BE4E}
 *
 *              Lichtsteuerung
 *             	{14AE3EA4-76F6-4A36-BE98-628A4CB1EAC7}
 */

// Declare
declare(strict_types=1);

// Include
include_once __DIR__ . '/helper/autoload.php';

class Lichtsteuerung extends IPSModule
{
    // Helper
    use LS_backupRestore;
    use LS_checkConditions;
    use LS_isDayDetection;
    use LS_messageSink;
    use LS_presenceDetection;
    use LS_sunriseSunset;
    use LS_switchingTime;
    use LS_switchLight;
    use LS_trigger;
    use LS_twilightDetection;
    use LS_weeklySchedule;

    // Constants
    private const DEVICE_DELAY_MILLISECONDS = 250;
    private const EXECUTION_DELAY_MILLISECONDS = 100;

    public function Create()
    {
        // Never delete this line!
        parent::Create();
        // Register properties
        $this->RegisterProperties();
        // Create profiles
        $this->CreateProfiles();
        // Register variables
        $this->RegisterVariables();
        // Register switching timers
        $this->RegisterSwitchingTimers();
        // Register duty cycle timer
        $this->RegisterDutyCycleTimer();
    }

    public function ApplyChanges()
    {
        // Wait until IP-Symcon is started
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        // Never delete this line!
        parent::ApplyChanges();
        // Check runlevel
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }
        // Register messages
        $this->RegisterMessages();
        // Set switching timers
        $this->SetSwitchingTimes();
        // Create links
        $this->CreateLinks();
        // Set options
        $this->SetOptions();
        // Dimming presets
        $this->UpdateDimmingPresets();
        // Deactivate duty cycle timer
        $this->DeactivateDutyCycleTimer();
        // Turn light off
        $this->SwitchLight(0);
    }

    public function Destroy()
    {
        // Never delete this line!
        parent::Destroy();
        // Delete profiles
        $this->DeleteProfiles();
    }

    private function KernelReady()
    {
        $this->ApplyChanges();
    }

    public function ReloadConfiguration()
    {
        $this->ReloadForm();
    }

    public function GetConfigurationForm()
    {
        $formData = json_decode(file_get_contents(__DIR__ . '/form.json'));
        // Lights
        $lightVariables = json_decode($this->ReadPropertyString('Lights'));
        if (!empty($lightVariables)) {
            foreach ($lightVariables as $variable) {
                $rowColor = '';
                $id = $variable->ID;
                if ($id == 0 || !IPS_ObjectExists($id)) {
                    $rowColor = '#FFC0C0'; // light red
                }
                $formData->elements[2]->items[1]->values[] = ['rowColor' => $rowColor];
            }
        }
        // Trigger
        $triggerVariables = json_decode($this->ReadPropertyString('Triggers'));
        if (!empty($triggerVariables)) {
            foreach ($triggerVariables as $variable) {
                $rowColor = '';
                $id = $variable->ID;
                if ($id == 0 || !IPS_ObjectExists($id)) {
                    $rowColor = '#FFC0C0'; // light red
                }
                $formData->elements[9]->items[1]->values[] = ['rowColor' => $rowColor];
            }
        }
        // Registered messages
        $registeredVariables = $this->GetMessageList();
        foreach ($registeredVariables as $senderID => $messageID) {
            if (!IPS_ObjectExists($senderID)) {
                foreach ($messageID as $messageType) {
                    $this->UnregisterMessage($senderID, $messageType);
                }
                continue;
            } else {
                $senderName = IPS_GetName($senderID);
                $description = $senderName;
                $parentID = IPS_GetParent($senderID);
                if (is_int($parentID) && $parentID != 0 && @IPS_ObjectExists($parentID)) {
                    $description = IPS_GetName($parentID);
                }
            }
            switch ($messageID) {
                case [10001]:
                    $messageDescription = 'IPS_KERNELSTARTED';
                    break;

                case [10603]:
                    $messageDescription = 'VM_UPDATE';
                    break;

                case [10803]:
                    $messageDescription = 'EM_UPDATE';
                    break;

                default:
                    $messageDescription = 'keine Bezeichnung';
            }
            $formData->actions[1]->items[0]->values[] = [
                'Description'        => $description,
                'SenderID'           => $senderID,
                'SenderName'         => $senderName,
                'MessageID'          => $messageID,
                'MessageDescription' => $messageDescription];
        }
        return json_encode($formData);
    }

    //#################### Request action

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'AutomaticMode':
                $this->SetValue($Ident, $Value);
                break;

            case 'Light':
                switch ($Value) {
                    // Off
                    case 0:
                        $brightness = $this->ReadPropertyInteger('LightOffBrightness');
                        $this->SwitchLight($brightness, 0, 0);
                        break;

                    // Timer
                    case 1:
                        $brightness = $this->ReadPropertyInteger('TimerBrightness');
                        $dutyCycle = $this->ReadPropertyInteger('TimerDutyCycle');
                        $dutyCycleUnit = $this->ReadPropertyInteger('TimerDutyCycleUnit');
                        $this->SwitchLight($brightness, $dutyCycle, $dutyCycleUnit);
                        break;

                    // On
                    case 2:
                        $brightness = $this->ReadPropertyInteger('LightOnBrightness');
                        $this->SwitchLight($brightness, 0, 0);
                        break;

                }
                break;

            case 'Dimmer':
                $this->SwitchLight(intval($Value * 100), 0, 0);
                break;

            case 'DimmingPresets':
                $this->SwitchLight(intval($Value), 0, 0);
                break;

        }
    }

    //#################### Private

    private function RegisterProperties(): void
    {
        // Visibility
        $this->RegisterPropertyBoolean('EnableAutomaticMode', true);
        $this->RegisterPropertyBoolean('EnableLight', true);
        $this->RegisterPropertyInteger('LightOffBrightness', 0);
        $this->RegisterPropertyInteger('LightOnBrightness', 100);
        $this->RegisterPropertyInteger('TimerBrightness', 100);
        $this->RegisterPropertyInteger('TimerDutyCycle', 180);
        $this->RegisterPropertyInteger('TimerDutyCycleUnit', 0);
        $this->RegisterPropertyBoolean('EnableDimmer', true);
        $this->RegisterPropertyBoolean('EnableDimmingPresets', true);
        $this->RegisterPropertyString('DimmingPresets', '[{"DimmingValue":0,"DimmingText":"0 %"},{"DimmingValue":25,"DimmingText":"25 %"}, {"DimmingValue":50,"DimmingText":"50 %"},{"DimmingValue":75,"DimmingText":"75 %"},{"DimmingValue":100,"DimmingText":"100 %"}]');
        $this->RegisterPropertyBoolean('EnableDutyCycleInfo', true);
        $this->RegisterPropertyBoolean('EnableNextSwitchingTimeInfo', true);
        $this->RegisterPropertyBoolean('EnableSunrise', true);
        $this->RegisterPropertyBoolean('EnableSunset', true);
        $this->RegisterPropertyBoolean('EnableWeeklySchedule', true);
        $this->RegisterPropertyBoolean('EnableIsDay', true);
        $this->RegisterPropertyBoolean('EnableTwilight', true);
        $this->RegisterPropertyBoolean('EnablePresence', true);
        // Lights
        $this->RegisterPropertyString('Lights', '[]');
        // Switching times
        $this->RegisterPropertyString('SwitchingTimeOne', '[{"LabelSwitchingTimeOne":"","UseSettings":false,"SwitchingTime":"{\"hour\":0,\"minute\":0,\"second\":0}","Brightness":0,"ExecutionDelay":0,"DutyCycle":0,"DutyCycleUnit":0,"LabelSwitchingConditions":"","CheckAutomaticMode":0,"CheckLight":0,"CheckIsDay":0,"CheckTwilight":0,"CheckPresence":0}]');
        $this->RegisterPropertyString('SwitchingTimeTwo', '[{"LabelSwitchingTimeTwo":"","UseSettings":false,"SwitchingTime":"{\"hour\":0,\"minute\":0,\"second\":0}","Brightness":0,"ExecutionDelay":0,"DutyCycle":0,"DutyCycleUnit":0,"LabelSwitchingConditions":"","CheckAutomaticMode":0,"CheckLight":0,"CheckIsDay":0,"CheckTwilight":0,"CheckPresence":0}]');
        $this->RegisterPropertyString('SwitchingTimeThree', '[{"LabelSwitchingTimeThree":"","UseSettings":false,"SwitchingTime":"{\"hour\":0,\"minute\":0,\"second\":0}","Brightness":0,"ExecutionDelay":0,"DutyCycle":0,"DutyCycleUnit":0,"LabelSwitchingConditions":"","CheckAutomaticMode":0,"CheckLight":0,"CheckIsDay":0,"CheckTwilight":0,"CheckPresence":0}]');
        $this->RegisterPropertyString('SwitchingTimeFour', '[{"LabelSwitchingTimeFour":"","UseSettings":false,"SwitchingTime":"{\"hour\":0,\"minute\":0,\"second\":0}","Brightness":0,"ExecutionDelay":0,"DutyCycle":0,"DutyCycleUnit":0,"LabelSwitchingConditions":"","CheckAutomaticMode":0,"CheckLight":0,"CheckIsDay":0,"CheckTwilight":0,"CheckPresence":0}]');
        // Sunrise and sunset
        $this->RegisterPropertyString('Sunrise', '[{"LabelSunrise":"","UseSettings":false,"ID":0,"Brightness":0,"ExecutionDelay":0,"DutyCycle":0,"DutyCycleUnit":0,"LabelSwitchingConditions":"","CheckAutomaticMode":0,"CheckLight":0,"CheckIsDay":0,"CheckTwilight":0,"CheckPresence":0}]');
        $this->RegisterPropertyString('Sunset', '[{"LabelSunset":"","UseSettings":false,"ID":0,"Brightness":0,"ExecutionDelay":0,"DutyCycle":0,"DutyCycleUnit":0,"LabelSwitchingConditions":"","CheckAutomaticMode":0,"CheckLight":0,"CheckIsDay":0,"CheckTwilight":0,"CheckPresence":0}]');
        // Weekly schedule
        $this->RegisterPropertyInteger('WeeklySchedule', 0);
        $this->RegisterPropertyString('WeeklyScheduleActionOne', '[{"LabelWeeklyScheduleAction":"","UseSettings":false,"Brightness":0,"ExecutionDelay":0,"DutyCycle":0,"DutyCycleUnit":0,"LabelSwitchingConditions":"","CheckAutomaticMode":0,"CheckLight":0,"CheckIsDay":0,"CheckTwilight":0,"CheckPresence":0}]');
        $this->RegisterPropertyString('WeeklyScheduleActionTwo', '[{"LabelWeeklyScheduleAction":"","UseSettings":false,"Brightness":0,"ExecutionDelay":0,"DutyCycle":0,"DutyCycleUnit":0,"LabelSwitchingConditions":"","CheckAutomaticMode":0,"CheckLight":0,"CheckIsDay":0,"CheckTwilight":0,"CheckPresence":0}]');
        $this->RegisterPropertyString('WeeklyScheduleActionThree', '[{"LabelWeeklyScheduleAction":"","UseSettings":false,"Brightness":0,"ExecutionDelay":0,"DutyCycle":0,"DutyCycleUnit":0,"LabelSwitchingConditions":"","CheckAutomaticMode":0,"CheckLight":0,"CheckIsDay":0,"CheckTwilight":0,"CheckPresence":0}]');
        // Is day
        $this->RegisterPropertyInteger('IsDay', 0);
        $this->RegisterPropertyString('NightAction', '[{"LabelNightAction":"","UseSettings":false,"Brightness":0,"ExecutionDelay":0,"DutyCycle":0,"DutyCycleUnit":0,"LabelSwitchingConditions":"","CheckAutomaticMode":0,"CheckLight":0,"CheckTwilight":0,"CheckPresence":0}]');
        $this->RegisterPropertyString('DayAction', '[{"LabelDayAction":"","UseSettings":false,"Brightness":0,"ExecutionDelay":0,"DutyCycle":0,"DutyCycleUnit":0,"LabelSwitchingConditions":"","CheckAutomaticMode":0,"CheckLight":0,"CheckTwilight":0,"CheckPresence":0}]');
        // Twilight
        $this->RegisterPropertyInteger('TwilightStatus', 0);
        $this->RegisterPropertyString('TwilightDayAction', '[{"LabelTwilightDayAction":"","UseSettings":false,"Brightness":0,"ExecutionDelay":0,"DutyCycle":0,"DutyCycleUnit":0,"LabelSwitchingConditions":"","CheckAutomaticMode":0,"CheckLight":0,"CheckIsDay":0,"CheckPresence":0}]');
        $this->RegisterPropertyString('TwilightNightAction', '[{"LabelTwilightNightAction":"","UseSettings":false,"Brightness":0,"ExecutionDelay":0,"DutyCycle":0,"DutyCycleUnit":0,"LabelSwitchingConditions":"","CheckAutomaticMode":0,"CheckLight":0,"CheckIsDay":0,"CheckPresence":0}]');
        // Presence and absence
        $this->RegisterPropertyInteger('PresenceStatus', 0);
        $this->RegisterPropertyString('AbsenceAction', '[{"LabelAbsenceAction":"","UseSettings":false,"Brightness":0,"ExecutionDelay":0,"DutyCycle":0,"DutyCycleUnit":0,"LabelSwitchingConditions":"","CheckAutomaticMode":0,"CheckLight":0,"CheckIsDay":0,"CheckTwilight":0}]');
        $this->RegisterPropertyString('PresenceAction', '[{"LabelPresenceAction":"","UseSettings":false,"Brightness":0,"ExecutionDelay":0,"DutyCycle":0,"DutyCycleUnit":0,"LabelSwitchingConditions":"","CheckAutomaticMode":0,"CheckLight":0,"CheckIsDay":0,"CheckTwilight":0}]');
        // Triggers
        $this->RegisterPropertyString('Triggers', '[]');
    }

    private function CreateProfiles(): void
    {
        // Automatic mode
        $profile = 'LS.' . $this->InstanceID . '.AutomaticMode';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 0);
        }
        IPS_SetVariableProfileAssociation($profile, 0, 'Aus', 'Execute', -1);
        IPS_SetVariableProfileAssociation($profile, 1, 'An', 'Clock', 0x00FF00);
        // Light
        $profileName = 'LS.' . $this->InstanceID . '.Light';
        if (!IPS_VariableProfileExists($profileName)) {
            IPS_CreateVariableProfile($profileName, 1);
        }
        IPS_SetVariableProfileIcon($profile, 'Bulb');
        IPS_SetVariableProfileAssociation($profileName, 0, 'Aus', 'Bulb', 0x0000FF);
        IPS_SetVariableProfileAssociation($profileName, 1, 'Timer', 'Bulb', 0xFFFF00);
        IPS_SetVariableProfileAssociation($profileName, 2, 'An', 'Bulb', 0x00FF00);
        // Dimming presets
        $profile = 'LS.' . $this->InstanceID . '.DimmingPresets';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileIcon($profile, 'Intensity');
    }

    private function UpdateDimmingPresets(): void
    {
        // Dimming presets
        $profile = 'LS.' . $this->InstanceID . '.DimmingPresets';
        $associations = IPS_GetVariableProfile($profile)['Associations'];
        if (!empty($associations)) {
            foreach ($associations as $association) {
                // Delete
                IPS_SetVariableProfileAssociation($profile, $association['Value'], '', '', -1);
            }
        }
        $dimmingPresets = json_decode($this->ReadPropertyString('DimmingPresets'));
        if (!empty($dimmingPresets)) {
            foreach ($dimmingPresets as $preset) {
                // Create
                IPS_SetVariableProfileAssociation($profile, $preset->DimmingValue, $preset->DimmingText, '', -1);
            }
        }
    }

    private function DeleteProfiles(): void
    {
        $profiles = ['AutomaticMode', 'Light', 'DimmingPresets'];
        foreach ($profiles as $profile) {
            $profileName = 'LS.' . $this->InstanceID . '.' . $profile;
            if (@IPS_VariableProfileExists($profileName)) {
                IPS_DeleteVariableProfile($profileName);
            }
        }
    }

    private function RegisterVariables(): void
    {
        // Automatic mode
        $profile = 'LS.' . $this->InstanceID . '.AutomaticMode';
        $this->RegisterVariableBoolean('AutomaticMode', 'Automatik', $profile, 0);
        $this->EnableAction('AutomaticMode');
        // Light
        $profile = 'LS.' . $this->InstanceID . '.Light';
        $this->RegisterVariableInteger('Light', 'Licht', $profile, 1);
        $this->EnableAction('Light');
        // Dimmer
        $profile = '~Intensity.1';
        $this->RegisterVariableFloat('Dimmer', 'Helligkeit', $profile, 2);
        $this->EnableAction('Dimmer');
        // Dimming presets
        $profile = 'LS.' . $this->InstanceID . '.DimmingPresets';
        $this->RegisterVariableInteger('DimmingPresets', 'Dimmer Voreinstellungen', $profile, 3);
        $this->EnableAction('DimmingPresets');
        // Duty cycle info
        $this->RegisterVariableString('DutyCycleInfo', 'Einschaltdauer bis', '', 4);
        $id = $this->GetIDForIdent('DutyCycleInfo');
        IPS_SetIcon($id, 'Clock');
        // Next switching time
        $this->RegisterVariableString('NextSwitchingTimeInfo', 'Nächste Schaltzeit', '', 5);
        IPS_SetIcon($this->GetIDForIdent('NextSwitchingTimeInfo'), 'Information');
    }

    private function CreateLinks(): void
    {
        // Sunrise
        $targetID = 0;
        $sunrise = json_decode($this->ReadPropertyString('Sunrise'), true)[0];
        if (!empty($sunrise)) {
            if ($sunrise['UseSettings']) {
                $targetID = $sunrise['ID'];
            }
        }
        $linkID = @IPS_GetLinkIDByName('Nächster Sonnenaufgang', $this->InstanceID);
        if ($targetID != 0 && @IPS_ObjectExists($targetID)) {
            // Check for existing link
            if ($linkID === false) {
                $linkID = IPS_CreateLink();
            }
            IPS_SetParent($linkID, $this->InstanceID);
            IPS_SetPosition($linkID, 6);
            IPS_SetName($linkID, 'Nächster Sonnenaufgang');
            IPS_SetIcon($linkID, 'Sun');
            IPS_SetLinkTargetID($linkID, $targetID);
        } else {
            if ($linkID !== false) {
                IPS_SetHidden($linkID, true);
            }
        }
        // Sunset
        $targetID = 0;
        $sunrise = json_decode($this->ReadPropertyString('Sunset'), true)[0];
        if (!empty($sunrise)) {
            if ($sunrise['UseSettings']) {
                $targetID = $sunrise['ID'];
            }
        }
        $linkID = @IPS_GetLinkIDByName('Nächster Sonnenuntergang', $this->InstanceID);
        if ($targetID != 0 && @IPS_ObjectExists($targetID)) {
            // Check for existing link
            if ($linkID === false) {
                $linkID = IPS_CreateLink();
            }
            IPS_SetParent($linkID, $this->InstanceID);
            IPS_SetPosition($linkID, 7);
            IPS_SetName($linkID, 'Nächster Sonnenuntergang');
            IPS_SetIcon($linkID, 'Moon');
            IPS_SetLinkTargetID($linkID, $targetID);
        } else {
            if ($linkID !== false) {
                IPS_SetHidden($linkID, true);
            }
        }
        // Weekly schedule
        $targetID = $this->ReadPropertyInteger('WeeklySchedule');
        $linkID = @IPS_GetLinkIDByName('Nächstes Wochenplanereignis', $this->InstanceID);
        if ($targetID != 0 && @IPS_ObjectExists($targetID)) {
            // Check for existing link
            if ($linkID === false) {
                $linkID = IPS_CreateLink();
            }
            IPS_SetParent($linkID, $this->InstanceID);
            IPS_SetPosition($linkID, 7);
            IPS_SetName($linkID, 'Nächstes Wochenplanereignis');
            IPS_SetIcon($linkID, 'Calendar');
            IPS_SetLinkTargetID($linkID, $targetID);
        } else {
            if ($linkID !== false) {
                IPS_SetHidden($linkID, true);
            }
        }
        // Is day
        $targetID = $this->ReadPropertyInteger('IsDay');
        $linkID = @IPS_GetLinkIDByName('Ist es Tag', $this->InstanceID);
        if ($targetID != 0 && @IPS_ObjectExists($targetID)) {
            // Check for existing link
            if ($linkID === false) {
                $linkID = IPS_CreateLink();
            }
            IPS_SetParent($linkID, $this->InstanceID);
            IPS_SetPosition($linkID, 8);
            IPS_SetName($linkID, 'Ist es Tag');
            IPS_SetLinkTargetID($linkID, $targetID);
        } else {
            if ($linkID !== false) {
                IPS_SetHidden($linkID, true);
            }
        }
        // Twilight
        $targetID = $this->ReadPropertyInteger('TwilightStatus');
        $linkID = @IPS_GetLinkIDByName('Dämmerungsstatus', $this->InstanceID);
        if ($targetID != 0 && @IPS_ObjectExists($targetID)) {
            // Check for existing link
            if ($linkID === false) {
                $linkID = IPS_CreateLink();
            }
            IPS_SetParent($linkID, $this->InstanceID);
            IPS_SetPosition($linkID, 9);
            IPS_SetName($linkID, 'Dämmerungsstatus');
            IPS_SetLinkTargetID($linkID, $targetID);
        } else {
            if ($linkID !== false) {
                IPS_SetHidden($linkID, true);
            }
        }
        // Presence
        $targetID = $this->ReadPropertyInteger('PresenceStatus');
        $linkID = @IPS_GetLinkIDByName('Anwesenheitsstatus', $this->InstanceID);
        if ($targetID != 0 && @IPS_ObjectExists($targetID)) {
            // Check for existing link
            if ($linkID === false) {
                $linkID = IPS_CreateLink();
            }
            IPS_SetParent($linkID, $this->InstanceID);
            IPS_SetPosition($linkID, 10);
            IPS_SetName($linkID, 'Anwesenheitsstatus');
            IPS_SetLinkTargetID($linkID, $targetID);
        } else {
            if ($linkID !== false) {
                IPS_SetHidden($linkID, true);
            }
        }
    }

    private function SetOptions(): void
    {
        // Automatic mode
        IPS_SetHidden($this->GetIDForIdent('AutomaticMode'), !$this->ReadPropertyBoolean('EnableAutomaticMode'));
        // Light
        IPS_SetHidden($this->GetIDForIdent('Light'), !$this->ReadPropertyBoolean('EnableLight'));
        // Dimmer
        IPS_SetHidden($this->GetIDForIdent('Dimmer'), !$this->ReadPropertyBoolean('EnableDimmer'));
        // Dimming Presets
        IPS_SetHidden($this->GetIDForIdent('DimmingPresets'), !$this->ReadPropertyBoolean('EnableDimmingPresets'));
        // Duty cycle info
        IPS_SetHidden($this->GetIDForIdent('DutyCycleInfo'), !$this->ReadPropertyBoolean('EnableDutyCycleInfo'));
        // Next switching time info
        $hide = !$this->ReadPropertyBoolean('EnableNextSwitchingTimeInfo');
        $properties = ['SwitchingTimeOne', 'SwitchingTimeTwo', 'SwitchingTimeThree', 'SwitchingTimeFour'];
        foreach ($properties as $property) {
            $use = json_decode($this->ReadPropertyString('SwitchingTimeOne'), true)[0]['UseSettings'];
            if (!$use) {
                $hide = true;
            }
        }
        IPS_SetHidden($this->GetIDForIdent('NextSwitchingTimeInfo'), $hide);
        // Sunrise
        $id = @IPS_GetLinkIDByName('Nächster Sonnenaufgang', $this->InstanceID);
        if ($id !== false) {
            $hide = true;
            $settings = json_decode($this->ReadPropertyString('Sunrise'), true)[0];
            $targetID = $settings['ID'];
            if ($targetID != 0 && @IPS_ObjectExists($targetID)) {
                if ($settings['UseSettings']) {
                    $hide = false;
                }
            }
            IPS_SetHidden($id, $hide);
        }
        // Sunset
        $id = @IPS_GetLinkIDByName('Nächster Sonnenuntergang', $this->InstanceID);
        if ($id !== false) {
            $hide = true;
            $settings = json_decode($this->ReadPropertyString('Sunset'), true)[0];
            $targetID = $settings['ID'];
            if ($targetID != 0 && @IPS_ObjectExists($targetID)) {
                if ($settings['UseSettings']) {
                    $hide = false;
                }
            }
            IPS_SetHidden($id, $hide);
        }
        // Weekly schedule
        $id = @IPS_GetLinkIDByName('Nächstes Wochenplanereignis', $this->InstanceID);
        if ($id !== false) {
            $hide = true;
            if ($this->ReadPropertyBoolean('EnableWeeklySchedule')) {
                if ($this->ValidateWeeklySchedule()) {
                    $hide = false;
                }
            }
            IPS_SetHidden($id, $hide);
        }
        // Is day
        $id = @IPS_GetLinkIDByName('Ist es Tag', $this->InstanceID);
        if ($id !== false) {
            $hide = true;
            $targetID = $this->ReadPropertyInteger('IsDay');
            if ($targetID != 0 && @IPS_ObjectExists($targetID)) {
                $profile = 'Location.' . $targetID . '.IsDay';
                if (!IPS_VariableProfileExists($profile)) {
                    IPS_CreateVariableProfile($profile, 0);
                    IPS_SetVariableProfileAssociation($profile, 0, 'Es ist Nacht', 'Moon', 0x0000FF);
                    IPS_SetVariableProfileAssociation($profile, 1, 'Es ist Tag', 'Sun', 0xFFFF00);
                    IPS_SetVariableCustomProfile($targetID, $profile);
                }
                if ($this->ReadPropertyBoolean('EnableIsDay')) {
                    $hide = false;
                }
            }
            IPS_SetHidden($id, $hide);
        }
        // Twilight
        $id = @IPS_GetLinkIDByName('Dämmerungsstatus', $this->InstanceID);
        if ($id !== false) {
            $hide = true;
            $targetID = $this->ReadPropertyInteger('TwilightStatus');
            if ($targetID != 0 && @IPS_ObjectExists($targetID)) {
                if ($this->ReadPropertyBoolean('EnableTwilight')) {
                    $hide = false;
                }
            }
            IPS_SetHidden($id, $hide);
        }
        // Presence
        $id = @IPS_GetLinkIDByName('Anwesenheitsstatus', $this->InstanceID);
        if ($id !== false) {
            $hide = true;
            $targetID = $this->ReadPropertyInteger('PresenceStatus');
            if ($targetID != 0 && @IPS_ObjectExists($targetID)) {
                if ($this->ReadPropertyBoolean('EnablePresence')) {
                    $hide = false;
                }
            }
            IPS_SetHidden($id, $hide);
        }
    }

    public function CreateScriptExample(): void
    {
        $scriptID = IPS_CreateScript(0);
        IPS_SetName($scriptID, 'Beispielskript (Lichtsteuerung #' . $this->InstanceID . ')');
        $scriptContent = "<?php\n\n// Methode:\n// LS_SwitchLight(integer \$InstanceID, integer \$Brightness, integer \$DutyCycle, integer \$DutyCycleUnit);\n\n### Beispiele:\n\n// Licht ausschalten:\nLS_SwitchLight(" . $this->InstanceID . ", 0, 0, 0);\n\n// Licht für 180 Sekunden einschalten:\nLS_SwitchLight(" . $this->InstanceID . ", 100, 180, 0);\n\n// Licht für 5 Minuten einschalten:\nLS_SwitchLight(" . $this->InstanceID . ", 100, 5, 1);\n\n// Licht mit 50% Helligkeit einschalten:\nLS_SwitchLight(" . $this->InstanceID . ', 50, 0, 0);';
        IPS_SetScriptContent($scriptID, $scriptContent);
        IPS_SetParent($scriptID, $this->InstanceID);
        IPS_SetPosition($scriptID, 100);
        IPS_SetHidden($scriptID, true);
        if ($scriptID != 0) {
            echo 'Beispielskript wurde erfolgreich erstellt!';
        }
    }
}