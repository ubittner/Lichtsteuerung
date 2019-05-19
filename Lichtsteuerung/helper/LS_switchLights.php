<?php

// Declare
declare(strict_types=1);

trait LS_switchLights
{
    //#################### Automatic mode

    /**
     * Sets the automatic mode.
     *
     * @param bool $State
     */
    public function SetAutomaticMode(bool $State)
    {
        // Set automatic mode
        $this->SetValue('AutomaticMode', $State);

        // Set timer
        $this->SetSwitchLightsOnTimer();
        $this->SetSwitchLightsOffTimer();
    }

    //#################### Switch Lights

    /**
     * Switches the assigned and used lights.
     *
     * @param bool $State
     * @param string $Trigger
     */
    public function SwitchLights(bool $State, string $Trigger)
    {
        $lights = json_decode($this->ReadPropertyString('Lights'));
        if (empty($lights)) {
            return;
        }
        foreach ($lights as $light) {
            $this->ToggleLight($light->VariableID, $State);
        }
        if ($Trigger == 'Timer') {
            $this->SetSwitchLightsOnTimer();
            $this->SetSwitchLightsOffTimer();
        }
    }

    /**
     * Toggles the light.
     *
     * @param int $LightID
     * @param bool $State
     */
    public function ToggleLight(int $LightID, bool $State)
    {
        $ident = $this->CheckLightIdent($LightID);
        if ($ident) {
            $executed = true;
            $toggle = @RequestAction($LightID, $State);
            if (!$toggle) {
                $toggleAgain = @RequestAction($LightID, $State);
                if (!$toggleAgain) {
                    $executed = false;
                    $lights = json_decode($this->ReadPropertyString('Lights'), true);
                    if (!empty($lights)) {
                        $position = array_search($LightID, array_column($lights, 'VariableID'));
                        if (is_int($position)) {
                            $name = $lights[$position]['Description'];
                            $this->LogMessage($name . ' konnte nicht geschaltet werden!', KL_WARNING);
                        }
                    }
                }
            }
            if ($State && $executed) {
                $this->SetValue('Lights', true);
            }
            if (!$State || !$executed) {
                $this->CheckLightsState();
            }
        }
    }

    //#################### Check state

    /**
     * Checks the state of all assigned and used lights.
     *
     */
    protected function CheckLightsState()
    {
        $state = false;
        $lights = json_decode($this->ReadPropertyString('Lights'));
        if (!empty($lights)) {
            foreach ($lights as $light) {
                if ($light->UseLight) {
                    $lightID = $light->VariableID;
                    $ident = $this->CheckLightIdent($lightID);
                    if ($ident) {
                        if (GetValue($lightID)) {
                            $state = true;
                        }
                    }
                }
            }
        }
        $this->SetValue('Lights', $state);
    }

    //#################### Check ident

    /**
     * Checks if the ident of the light is STATE.
     *
     * @param int $LightID
     * @return bool
     */
    protected function CheckLightIdent(int $LightID): bool
    {
        $ident = false;
        $objectIdent = IPS_GetObject($LightID)['ObjectIdent'];
        if ($objectIdent == 'STATE') {
            $ident = true;
        }
        return $ident;
    }
}