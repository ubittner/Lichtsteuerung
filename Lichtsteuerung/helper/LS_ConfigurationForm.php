<?php

/**
 * @project       Lichtsteuerung/helper
 * @file          LS_ConfigurationForm.php
 * @author        Ulrich Bittner
 * @copyright     2023 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpUndefinedFunctionInspection */
/** @noinspection DuplicatedCode */

declare(strict_types=1);

trait LS_ConfigurationForm
{
    /**
     * Reloads the configuration form.
     *
     * @return void
     */
    public function ReloadConfig(): void
    {
        $this->ReloadForm();
    }

    /**
     * Expands or collapses the expansion panels.
     *
     * @param bool $State
     * false =  collapse,
     * true =   expand
     * @return void
     */
    public function ExpandExpansionPanels(bool $State): void
    {
        for ($i = 1; $i <= 13; $i++) {
            $this->UpdateFormField('Panel' . $i, 'expanded', $State);
        }
    }

    /**
     * Modifies a configuration button.
     *
     * @param string $Field
     * @param string $Caption
     * @param int $ObjectID
     * @return void
     */
    public function ModifyButton(string $Field, string $Caption, int $ObjectID): void
    {
        $state = false;
        if ($ObjectID > 1 && @IPS_ObjectExists($ObjectID)) {
            $state = true;
        }
        $this->UpdateFormField($Field, 'caption', $Caption);
        $this->UpdateFormField($Field, 'visible', $state);
        $this->UpdateFormField($Field, 'objectID', $ObjectID);
    }

    /**
     * Modifies a trigger list configuration button
     *
     * @param string $Field
     * @param string $Condition
     * @return void
     */
    public function ModifyTriggerListButton(string $Field, string $Condition): void
    {
        $id = 0;
        $state = false;
        //Get variable id
        $primaryCondition = json_decode($Condition, true);
        if (array_key_exists(0, $primaryCondition)) {
            if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                if ($id > 1 && @IPS_ObjectExists($id)) {
                    $state = true;
                }
            }
        }
        $this->UpdateFormField($Field, 'caption', 'ID ' . $id . ' Bearbeiten');
        $this->UpdateFormField($Field, 'visible', $state);
        $this->UpdateFormField($Field, 'objectID', $id);
    }

    /**
     * Gets the configuration form.
     *
     * @return false|string
     * @throws Exception
     */
    public function GetConfigurationForm()
    {
        $form = [];

        ########## Elements

        //Configuration buttons
        $form['elements'][0] =
            [
                'type'  => 'RowLayout',
                'items' => [
                    [
                        'type'    => 'Button',
                        'caption' => 'Konfiguration ausklappen',
                        'onClick' => self::MODULE_PREFIX . '_ExpandExpansionPanels($id, true);'
                    ],
                    [
                        'type'    => 'Button',
                        'caption' => 'Konfiguration einklappen',
                        'onClick' => self::MODULE_PREFIX . '_ExpandExpansionPanels($id, false);'
                    ],
                    [
                        'type'    => 'Button',
                        'caption' => 'Konfiguration neu laden',
                        'onClick' => self::MODULE_PREFIX . '_ReloadConfig($id);'
                    ]
                ]
            ];

        //Info
        $library = IPS_GetLibrary(self::LIBRARY_GUID);
        $module = IPS_GetModule(self::MODULE_GUID);
        $form['elements'][] = [
            'type'     => 'ExpansionPanel',
            'name'     => 'Panel1',
            'caption'  => 'Info',
            'expanded' => false,
            'items'    => [
                [
                    'type'    => 'Label',
                    'caption' => 'Mit dieser Instanz kann ein Licht geschaltet werden.'
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Ein Gemeinschaftsprojekt von Normen Thiel und Ulrich Bittner.'
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => "ID:\t\t\t" . $this->InstanceID
                ],
                [
                    'type'    => 'Label',
                    'caption' => "Modul:\t\t" . $module['ModuleName']
                ],
                [
                    'type'    => 'Label',
                    'caption' => "Präfix:\t\t" . $module['Prefix']
                ],
                [
                    'type'    => 'Label',
                    'caption' => "Version:\t\t" . $library['Version'] . '-' . $library['Build'] . ', ' . date('d.m.Y', $library['Date'])
                ],
                [
                    'type'    => 'Label',
                    'caption' => "Entwickler:\t" . $library['Author']
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'Note',
                    'caption' => 'Notiz',
                    'width'   => '600px'
                ]
            ]
        ];

        //Light
        $lightVariable = $this->ReadPropertyInteger('Light');
        $enableLightVariableButton = false;
        if ($lightVariable > 1 && @IPS_ObjectExists($lightVariable)) {
            $enableLightVariableButton = true;
        }

        $form['elements'][] = [
            'type'     => 'ExpansionPanel',
            'name'     => 'Panel2',
            'caption'  => 'Licht',
            'expanded' => false,
            'items'    => [
                [
                    'type'    => 'Label',
                    'caption' => 'Bitte wählen Sie die zu schaltende Variable aus:',
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'     => 'SelectVariable',
                            'name'     => 'Light',
                            'caption'  => 'Licht',
                            'width'    => '600px',
                            'onChange' => self::MODULE_PREFIX . '_ModifyButton($id, "LightVariableConfigurationButton", "ID " . $Light . " bearbeiten", $Light);'
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'LightVariableConfigurationButton',
                            'caption'  => 'ID ' . $lightVariable . ' bearbeiten',
                            'visible'  => $enableLightVariableButton,
                            'objectID' => $lightVariable
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Schaltoptionen',
                    'bold'    => true
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'SwitchChangesOnly',
                    'caption' => 'Nur Änderungen schalten',
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Ruhe-Modus',
                    'bold'    => true
                ],
                [
                    'type'    => 'NumberSpinner',
                    'name'    => 'SleepDuration',
                    'caption' => 'Dauer',
                    'suffix'  => 'h'
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Licht Aus',
                    'bold'    => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'LightOff',
                    'caption'  => 'Aktionen',
                    'add'      => true,
                    'delete'   => true,
                    'rowCount' => 3,
                    'columns'  => [
                        [
                            'name'    => 'LabelLightOff',
                            'caption' => 'Licht Aus:',
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'    => 'UseSettings',
                            'caption' => 'Aktiviert',
                            'add'     => true,
                            'width'   => '100px',
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'Brightness',
                            'caption' => 'Helligkeit',
                            'width'   => '100px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' %',
                                'minimum' => 0,
                                'maximum' => 100
                            ]
                        ],
                        [
                            'name'    => 'UpdateLastBrightness',
                            'caption' => 'Letzte Helligkeit anpassen',
                            'add'     => false,
                            'width'   => '220px',
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'LabelSwitchingConditions',
                            'caption' => "\nBedingungen:",
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'    => 'CheckAutomaticMode',
                            'caption' => 'Automatik',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckSleepMode',
                            'caption' => 'Ruhe-Modus',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckLightMode',
                            'caption' => 'Licht',
                            'add'     => 0,
                            'width'   => '150px',
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Timer',
                                        'value'   => 2
                                    ],
                                    [
                                        'caption' => 'Timer - An',
                                        'value'   => 3
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 4
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckIsDay',
                            'caption' => 'Ist es Tag',
                            'add'     => 0,
                            'width'   => '150px',
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Es ist Nacht',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Es ist Tag',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckTwilight',
                            'caption' => 'Dämmerung',
                            'add'     => 0,
                            'width'   => '200px',
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Es ist Tag',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Es ist Nacht',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckPresence',
                            'caption' => 'Anwesenheit',
                            'add'     => 0,
                            'width'   => '200px',
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Abwesenheit',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Anwesenheit',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Licht Timer',
                    'bold'    => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'Timer',
                    'caption'  => 'Aktionen',
                    'add'      => true,
                    'delete'   => true,
                    'rowCount' => 3,
                    'columns'  => [
                        [
                            'name'    => 'LabelTimer',
                            'caption' => 'Licht Timer:',
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'    => 'UseSettings',
                            'caption' => 'Aktiviert',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'Brightness',
                            'caption' => 'Helligkeit',
                            'width'   => '100px',
                            'add'     => 50,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' %',
                                'minimum' => 0,
                                'maximum' => 100
                            ]
                        ],
                        [
                            'name'    => 'UpdateLastBrightness',
                            'caption' => 'Letzte Helligkeit anpassen',
                            'add'     => false,
                            'width'   => '220px',
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'DutyCycle',
                            'caption' => 'Einschaltdauer',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type' => 'NumberSpinner'
                            ]
                        ],
                        [
                            'name'    => 'DutyCycleUnit',
                            'caption' => 'Einheit',
                            'width'   => '100px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Sekunden',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Minuten',
                                        'value'   => 1
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'LabelSwitchingConditions',
                            'caption' => "\nBedingungen:",
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'    => 'CheckAutomaticMode',
                            'caption' => 'Automatik',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckSleepMode',
                            'caption' => 'Ruhe-Modus',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckLightMode',
                            'caption' => 'Licht',
                            'add'     => 0,
                            'width'   => '150px',
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Timer',
                                        'value'   => 2
                                    ],
                                    [
                                        'caption' => 'Timer - An',
                                        'value'   => 3
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 4
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckIsDay',
                            'caption' => 'Ist es Tag',
                            'add'     => 0,
                            'width'   => '150px',
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Es ist Nacht',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Es ist Tag',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckTwilight',
                            'caption' => 'Dämmerung',
                            'add'     => 0,
                            'width'   => '200px',
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Es ist Tag',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Es ist Nacht',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckPresence',
                            'caption' => 'Anwesenheit',
                            'add'     => 0,
                            'width'   => '200px',
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Abwesenheit',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Anwesenheit',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'LabelOperationalAction',
                            'caption' => "\nAblaufaktion:",
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'    => 'OperationalAction',
                            'caption' => 'Ablaufaktion',
                            'width'   => '170px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Letzte Helligkeit',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Definierte Helligkeit',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'DefinedBrightness',
                            'caption' => 'Definierte Helligkeit',
                            'width'   => '190px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' %',
                                'minimum' => 0,
                                'maximum' => 100
                            ]
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Licht An',
                    'bold'    => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'LightOn',
                    'caption'  => 'Aktionen',
                    'add'      => true,
                    'delete'   => true,
                    'rowCount' => 3,
                    'columns'  => [
                        [
                            'name'    => 'LabelLightOn',
                            'caption' => 'Licht An:',
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'    => 'UseSettings',
                            'caption' => 'Aktiviert',
                            'add'     => true,
                            'width'   => '100px',
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'Brightness',
                            'caption' => 'Helligkeit',
                            'width'   => '100px',
                            'add'     => 100,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' %',
                                'minimum' => 0,
                                'maximum' => 100
                            ]
                        ],
                        [
                            'name'    => 'UpdateLastBrightness',
                            'caption' => 'Letzte Helligkeit anpassen',
                            'add'     => false,
                            'width'   => '220px',
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'LabelSwitchingConditions',
                            'caption' => "\nBedingungen:",
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'    => 'CheckAutomaticMode',
                            'caption' => 'Automatik',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckSleepMode',
                            'caption' => 'Ruhe-Modus',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckLightMode',
                            'caption' => 'Licht',
                            'add'     => 0,
                            'width'   => '150px',
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Timer',
                                        'value'   => 2
                                    ],
                                    [
                                        'caption' => 'Timer - An',
                                        'value'   => 3
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 4
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckIsDay',
                            'caption' => 'Ist es Tag',
                            'add'     => 0,
                            'width'   => '150px',
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Es ist Nacht',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Es ist Tag',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckTwilight',
                            'caption' => 'Dämmerung',
                            'add'     => 0,
                            'width'   => '200px',
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Es ist Tag',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Es ist Nacht',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckPresence',
                            'caption' => 'Anwesenheit',
                            'add'     => 0,
                            'width'   => '200px',
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Abwesenheit',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Anwesenheit',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Helligkeit',
                    'bold'    => true
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'DimmerUpdateLastBrightness',
                    'caption' => 'Letzte Helligkeit anpassen',
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Helligkeit Voreinstellungen',
                    'bold'    => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'DimmingPresets',
                    'rowCount' => 5,
                    'add'      => true,
                    'delete'   => true,
                    'sort'     => [
                        'column'    => 'DimmingValue',
                        'direction' => 'ascending'
                    ],
                    'columns' => [
                        [
                            'name'    => 'DimmingValue',
                            'caption' => 'Wert',
                            'width'   => '100px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' %',
                                'minimum' => 0,
                                'maximum' => 100
                            ]
                        ],
                        [
                            'name'    => 'DimmingText',
                            'caption' => 'Text',
                            'width'   => '425px',
                            'add'     => '',
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ]
                    ]
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'DimmingPresetsUpdateLastBrightness',
                    'caption' => 'Letzte Helligkeit anpassen',
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Letzte Helligkeit',
                    'bold'    => true
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableLastBrightnessManualChange',
                    'caption' => 'Manuelle Änderung erlauben',
                ]
            ]
        ];

        //Check status
        $form['elements'][] =
            [
                'type'    => 'ExpansionPanel',
                'name'    => 'Panel3',
                'caption' => 'Lichtstatus',
                'items'   => [
                    [
                        'type'    => 'Label',
                        'caption' => 'Bitte wählen Sie aus, wie der Lichtstatus aktualisiert werden soll:',
                    ],
                    [
                        'type'    => 'CheckBox',
                        'name'    => 'UseImmediateLightStatusUpdate',
                        'caption' => 'Sofortige Aktualisierung',
                    ],
                    [
                        'type'    => 'NumberSpinner',
                        'name'    => 'LightStatusUpdateInterval',
                        'caption' => 'Intervall',
                        'minimum' => 0,
                        'suffix'  => 'Sekunden'
                    ],
                    [
                        'type'    => 'CheckBox',
                        'name'    => 'LightStatusUpdateLastBrightness',
                        'caption' => 'Letzte Helligkeit anpassen',
                    ]
                ]
            ];

        $form['elements'][] = [
            'type'     => 'ExpansionPanel',
            'name'     => 'Panel4',
            'caption'  => 'Schaltzeiten',
            'expanded' => false,
            'items'    => [
                [
                    'type'    => 'Label',
                    'caption' => 'Bitte konfigurieren Sie die Schaltzeiten:',
                ],
                //First switching time
                [
                    'type'    => 'Label',
                    'caption' => 'Schaltzeit 1',
                    'bold'    => true
                ],
                [
                    'type'    => 'SelectTime',
                    'name'    => 'SwitchingTimeOne',
                    'caption' => 'Uhrzeit',
                    'width'   => '300px'
                ],
                [
                    'type'     => 'List',
                    'name'     => 'SwitchingTimeOneActions',
                    'caption'  => 'Aktionen',
                    'add'      => true,
                    'delete'   => true,
                    'rowCount' => 3,
                    'columns'  => [
                        [
                            'name'    => 'LabelSwitchingTime',
                            'caption' => 'Schaltzeit 1:',
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'  => 'UseSettings',
                            'label' => 'Aktiviert',
                            'width' => '100px',
                            'add'   => true,
                            'edit'  => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'Brightness',
                            'caption' => 'Helligkeit',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' %',
                                'minimum' => 0,
                                'maximum' => 100
                            ]
                        ],
                        [
                            'name'    => 'UpdateLastBrightness',
                            'caption' => 'Letzte Helligkeit anpassen',
                            'add'     => false,
                            'width'   => '220px',
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'ExecutionDelay',
                            'caption' => 'Verzögerung',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' s',
                                'minimum' => 0,
                                'maximum' => 10
                            ]
                        ],
                        [
                            'name'    => 'LabelSwitchingConditions',
                            'caption' => "\nBedingungen:",
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'    => 'CheckAutomaticMode',
                            'caption' => 'Automatik',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckSleepMode',
                            'caption' => 'Ruhe-Modus',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckLightMode',
                            'caption' => 'Licht',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Timer',
                                        'value'   => 2
                                    ],
                                    [
                                        'caption' => 'Timer - An',
                                        'value'   => 3
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 4
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckIsDay',
                            'caption' => 'Ist es Tag',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Es ist Nacht',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Es ist Tag',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckTwilight',
                            'caption' => 'Dämmerung',
                            'width'   => '200px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Es ist Tag',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Es ist Nacht',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckPresence',
                            'caption' => 'Anwesenheit',
                            'width'   => '200px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Abwesenheit',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Anwesenheit',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                //Second switching time
                [
                    'type'    => 'Label',
                    'caption' => 'Schaltzeit 2',
                    'bold'    => true
                ],
                [
                    'type'    => 'SelectTime',
                    'name'    => 'SwitchingTimeTwo',
                    'caption' => 'Uhrzeit',
                    'width'   => '300px'
                ],
                [
                    'type'     => 'List',
                    'name'     => 'SwitchingTimeTwoActions',
                    'caption'  => 'Aktionen',
                    'add'      => true,
                    'delete'   => true,
                    'rowCount' => 3,
                    'columns'  => [
                        [
                            'name'    => 'LabelSwitchingTime',
                            'caption' => 'Schaltzeit 2:',
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'  => 'UseSettings',
                            'label' => 'Aktiviert',
                            'width' => '100px',
                            'add'   => true,
                            'edit'  => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'Brightness',
                            'caption' => 'Helligkeit',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' %',
                                'minimum' => 0,
                                'maximum' => 100
                            ]
                        ],
                        [
                            'name'    => 'UpdateLastBrightness',
                            'caption' => 'Letzte Helligkeit anpassen',
                            'add'     => false,
                            'width'   => '220px',
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'ExecutionDelay',
                            'caption' => 'Verzögerung',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' s',
                                'minimum' => 0,
                                'maximum' => 10
                            ]
                        ],
                        [
                            'name'    => 'LabelSwitchingConditions',
                            'caption' => "\nBedingungen:",
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'    => 'CheckAutomaticMode',
                            'caption' => 'Automatik',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckSleepMode',
                            'caption' => 'Ruhe-Modus',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckLightMode',
                            'caption' => 'Licht',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Timer',
                                        'value'   => 2
                                    ],
                                    [
                                        'caption' => 'Timer - An',
                                        'value'   => 3
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 4
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckIsDay',
                            'caption' => 'Ist es Tag',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Es ist Nacht',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Es ist Tag',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckTwilight',
                            'caption' => 'Dämmerung',
                            'width'   => '200px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Es ist Tag',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Es ist Nacht',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckPresence',
                            'caption' => 'Anwesenheit',
                            'width'   => '200px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Abwesenheit',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Anwesenheit',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                //Third switching time
                [
                    'type'    => 'Label',
                    'caption' => 'Schaltzeit 3',
                    'bold'    => true
                ],
                [
                    'type'    => 'SelectTime',
                    'name'    => 'SwitchingTimeThree',
                    'caption' => 'Uhrzeit',
                    'width'   => '300px'
                ],
                [
                    'type'     => 'List',
                    'name'     => 'SwitchingTimeThreeActions',
                    'caption'  => 'Aktionen',
                    'add'      => true,
                    'delete'   => true,
                    'rowCount' => 3,
                    'columns'  => [
                        [
                            'name'    => 'LabelSwitchingTime',
                            'caption' => 'Schaltzeit 3:',
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'  => 'UseSettings',
                            'label' => 'Aktiviert',
                            'width' => '100px',
                            'add'   => true,
                            'edit'  => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'Brightness',
                            'caption' => 'Helligkeit',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' %',
                                'minimum' => 0,
                                'maximum' => 100
                            ]
                        ],
                        [
                            'name'    => 'UpdateLastBrightness',
                            'caption' => 'Letzte Helligkeit anpassen',
                            'add'     => false,
                            'width'   => '220px',
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'ExecutionDelay',
                            'caption' => 'Verzögerung',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' s',
                                'minimum' => 0,
                                'maximum' => 10
                            ]
                        ],
                        [
                            'name'    => 'LabelSwitchingConditions',
                            'caption' => "\nBedingungen:",
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'    => 'CheckAutomaticMode',
                            'caption' => 'Automatik',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckSleepMode',
                            'caption' => 'Ruhe-Modus',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckLightMode',
                            'caption' => 'Licht',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Timer',
                                        'value'   => 2
                                    ],
                                    [
                                        'caption' => 'Timer - An',
                                        'value'   => 3
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 4
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckIsDay',
                            'caption' => 'Ist es Tag',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Es ist Nacht',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Es ist Tag',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckTwilight',
                            'caption' => 'Dämmerung',
                            'width'   => '200px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Es ist Tag',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Es ist Nacht',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckPresence',
                            'caption' => 'Anwesenheit',
                            'width'   => '200px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Abwesenheit',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Anwesenheit',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                //Fourth switching time
                [
                    'type'    => 'Label',
                    'caption' => 'Schaltzeit 4',
                    'bold'    => true
                ],
                [
                    'type'    => 'SelectTime',
                    'name'    => 'SwitchingTimeFour',
                    'caption' => 'Uhrzeit',
                    'width'   => '300px'
                ],
                [
                    'type'     => 'List',
                    'name'     => 'SwitchingTimeFourActions',
                    'caption'  => 'Aktionen',
                    'add'      => true,
                    'delete'   => true,
                    'rowCount' => 3,
                    'columns'  => [
                        [
                            'name'    => 'LabelSwitchingTime',
                            'caption' => 'Schaltzeit 4:',
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'  => 'UseSettings',
                            'label' => 'Aktiviert',
                            'width' => '100px',
                            'add'   => true,
                            'edit'  => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'Brightness',
                            'caption' => 'Helligkeit',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' %',
                                'minimum' => 0,
                                'maximum' => 100
                            ]
                        ],
                        [
                            'name'    => 'UpdateLastBrightness',
                            'caption' => 'Letzte Helligkeit anpassen',
                            'add'     => false,
                            'width'   => '220px',
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'ExecutionDelay',
                            'caption' => 'Verzögerung',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' s',
                                'minimum' => 0,
                                'maximum' => 10
                            ]
                        ],
                        [
                            'name'    => 'LabelSwitchingConditions',
                            'caption' => "\nBedingungen:",
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'    => 'CheckAutomaticMode',
                            'caption' => 'Automatik',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckSleepMode',
                            'caption' => 'Ruhe-Modus',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckLightMode',
                            'caption' => 'Licht',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Timer',
                                        'value'   => 2
                                    ],
                                    [
                                        'caption' => 'Timer - An',
                                        'value'   => 3
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 4
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckIsDay',
                            'caption' => 'Ist es Tag',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Es ist Nacht',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Es ist Tag',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckTwilight',
                            'caption' => 'Dämmerung',
                            'width'   => '200px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Es ist Tag',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Es ist Nacht',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckPresence',
                            'caption' => 'Anwesenheit',
                            'width'   => '200px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Abwesenheit',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Anwesenheit',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        //Sunrise
        $sunriseVariable = $this->ReadPropertyInteger('Sunrise');
        $enableSunriseVariableButton = false;
        if ($sunriseVariable > 1 && @IPS_ObjectExists($sunriseVariable)) {
            $enableSunriseVariableButton = true;
        }

        $form['elements'][] = [
            'type'     => 'ExpansionPanel',
            'name'     => 'Panel5',
            'caption'  => 'Sonnenaufgang',
            'expanded' => false,
            'items'    => [
                [
                    'type'    => 'Label',
                    'caption' => 'Bitte wählen Sie die Variable für den Sonnenaufgang aus:'
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'     => 'SelectVariable',
                            'name'     => 'Sunrise',
                            'caption'  => 'Sonnenaufgang',
                            'width'    => '600px',
                            'onChange' => self::MODULE_PREFIX . '_ModifyButton($id, "SunriseVariableConfigurationButton", "ID " . $Sunrise . " bearbeiten", $Sunrise);'
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'SunriseVariableConfigurationButton',
                            'caption'  => 'ID ' . $sunriseVariable . ' bearbeiten',
                            'visible'  => $enableSunriseVariableButton,
                            'objectID' => $sunriseVariable
                        ]
                    ]
                ],
                [
                    'type'     => 'List',
                    'name'     => 'SunriseActions',
                    'caption'  => 'Aktionen',
                    'add'      => true,
                    'delete'   => true,
                    'rowCount' => 3,
                    'columns'  => [
                        [
                            'name'    => 'LabelSunrise',
                            'caption' => 'Sonnenaufgang:',
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'  => 'UseSettings',
                            'label' => 'Aktiviert',
                            'width' => '100px',
                            'add'   => true,
                            'edit'  => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'Brightness',
                            'caption' => 'Helligkeit',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' %',
                                'minimum' => 0,
                                'maximum' => 100
                            ]
                        ],
                        [
                            'name'    => 'UpdateLastBrightness',
                            'caption' => 'Letzte Helligkeit anpassen',
                            'add'     => false,
                            'width'   => '220px',
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'ExecutionDelay',
                            'caption' => 'Verzögerung',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' s',
                                'minimum' => 0,
                                'maximum' => 10
                            ]
                        ],
                        [
                            'name'    => 'LabelSwitchingConditions',
                            'caption' => "\nBedingungen:",
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'    => 'CheckAutomaticMode',
                            'caption' => 'Automatik',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckSleepMode',
                            'caption' => 'Ruhe-Modus',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckLightMode',
                            'caption' => 'Licht',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Timer',
                                        'value'   => 2
                                    ],
                                    [
                                        'caption' => 'Timer - An',
                                        'value'   => 3
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 4
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckIsDay',
                            'caption' => 'Ist es Tag',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Es ist Nacht',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Es ist Tag',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckTwilight',
                            'caption' => 'Dämmerung',
                            'width'   => '200px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Es ist Tag',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Es ist Nacht',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckPresence',
                            'caption' => 'Anwesenheit',
                            'width'   => '200px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Abwesenheit',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Anwesenheit',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        //Sunset
        $sunsetVariable = $this->ReadPropertyInteger('Sunset');
        $enableSunsetVariableButton = false;
        if ($sunsetVariable > 1 && @IPS_ObjectExists($sunsetVariable)) {
            $enableSunsetVariableButton = true;
        }

        $form['elements'][] = [
            'type'     => 'ExpansionPanel',
            'name'     => 'Panel6',
            'caption'  => 'Sonnenuntergang',
            'expanded' => false,
            'items'    => [
                [
                    'type'    => 'Label',
                    'caption' => 'Bitte wählen Sie die Variable für den Sonnenuntergang aus:'
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'     => 'SelectVariable',
                            'name'     => 'Sunset',
                            'caption'  => 'Sonnenuntergang',
                            'width'    => '600px',
                            'onChange' => self::MODULE_PREFIX . '_ModifyButton($id, "SunsetVariableConfigurationButton", "ID " . $Sunset . " bearbeiten", $Sunset);'
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'SunsetVariableConfigurationButton',
                            'caption'  => 'ID ' . $sunsetVariable . ' bearbeiten',
                            'visible'  => $enableSunsetVariableButton,
                            'objectID' => $sunsetVariable
                        ]
                    ]
                ],
                [
                    'type'     => 'List',
                    'name'     => 'SunsetActions',
                    'caption'  => 'Aktionen',
                    'add'      => true,
                    'delete'   => true,
                    'rowCount' => 3,
                    'columns'  => [
                        [
                            'name'    => 'LabelSunset',
                            'caption' => 'Sonnenuntergang:',
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'  => 'UseSettings',
                            'label' => 'Aktiviert',
                            'width' => '100px',
                            'add'   => true,
                            'edit'  => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'Brightness',
                            'caption' => 'Helligkeit',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' %',
                                'minimum' => 0,
                                'maximum' => 100
                            ]
                        ],
                        [
                            'name'    => 'UpdateLastBrightness',
                            'caption' => 'Letzte Helligkeit anpassen',
                            'add'     => false,
                            'width'   => '220px',
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'ExecutionDelay',
                            'caption' => 'Verzögerung',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' s',
                                'minimum' => 0,
                                'maximum' => 10
                            ]
                        ],
                        [
                            'name'    => 'LabelSwitchingConditions',
                            'caption' => "\nBedingungen:",
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'    => 'CheckAutomaticMode',
                            'caption' => 'Automatik',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckSleepMode',
                            'caption' => 'Ruhe-Modus',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckLightMode',
                            'caption' => 'Licht',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Timer',
                                        'value'   => 2
                                    ],
                                    [
                                        'caption' => 'Timer - An',
                                        'value'   => 3
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 4
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckIsDay',
                            'caption' => 'Ist es Tag',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Es ist Nacht',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Es ist Tag',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckTwilight',
                            'caption' => 'Dämmerung',
                            'width'   => '200px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Es ist Tag',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Es ist Nacht',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckPresence',
                            'caption' => 'Anwesenheit',
                            'width'   => '200px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Abwesenheit',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Anwesenheit',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]

            ]
        ];

        //Weekly schedule
        $weeklySchedule = $this->ReadPropertyInteger('WeeklySchedule');
        $enableWeeklyScheduleButton = false;
        if ($weeklySchedule > 1 && @IPS_ObjectExists($weeklySchedule)) {
            $enableWeeklyScheduleButton = true;
        }

        $form['elements'][] = [
            'type'     => 'ExpansionPanel',
            'name'     => 'Panel7',
            'caption'  => 'Wochenplan',
            'expanded' => false,
            'items'    => [
                [
                    'type'    => 'Label',
                    'caption' => 'Bitte wählen Sie den Wochenplan aus:'
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'     => 'SelectEvent',
                            'name'     => 'WeeklySchedule',
                            'caption'  => 'Wochenplan',
                            'width'    => '600px',
                            'onChange' => self::MODULE_PREFIX . '_ModifyButton($id, "WeeklyScheduleConfigurationButton", "ID " . $WeeklySchedule . " bearbeiten", $WeeklySchedule);'
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'WeeklyScheduleConfigurationButton',
                            'caption'  => 'ID ' . $weeklySchedule . ' bearbeiten',
                            'visible'  => $enableWeeklyScheduleButton,
                            'objectID' => $weeklySchedule
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Licht Aus (ID 1)',
                    'bold'    => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'WeeklyScheduleActionOne',
                    'caption'  => 'Aktionen',
                    'add'      => true,
                    'delete'   => true,
                    'rowCount' => 3,
                    'columns'  => [
                        [
                            'name'    => 'LabelWeeklyScheduleAction',
                            'caption' => 'Wochenplan - Licht Aus',
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'  => 'UseSettings',
                            'label' => 'Aktiviert',
                            'width' => '100px',
                            'add'   => true,
                            'edit'  => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'Brightness',
                            'caption' => 'Helligkeit',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' %',
                                'minimum' => 0,
                                'maximum' => 100
                            ]
                        ],
                        [
                            'name'    => 'UpdateLastBrightness',
                            'caption' => 'Letzte Helligkeit anpassen',
                            'add'     => false,
                            'width'   => '220px',
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'ExecutionDelay',
                            'caption' => 'Verzögerung',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' s',
                                'minimum' => 0,
                                'maximum' => 10
                            ]
                        ],
                        [
                            'name'    => 'LabelSwitchingConditions',
                            'caption' => "\nBedingungen:",
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'    => 'CheckAutomaticMode',
                            'caption' => 'Automatik',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckSleepMode',
                            'caption' => 'Ruhe-Modus',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckLightMode',
                            'caption' => 'Licht',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Timer',
                                        'value'   => 2
                                    ],
                                    [
                                        'caption' => 'Timer - An',
                                        'value'   => 3
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 4
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckIsDay',
                            'caption' => 'Ist es Tag',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Es ist Nacht',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Es ist Tag',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckTwilight',
                            'caption' => 'Dämmerung',
                            'width'   => '200px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Es ist Tag',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Es ist Nacht',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckPresence',
                            'caption' => 'Anwesenheit',
                            'width'   => '200px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Abwesenheit',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Anwesenheit',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Licht An (ID 2)',
                    'bold'    => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'WeeklyScheduleActionTwo',
                    'caption'  => 'Aktionen',
                    'add'      => true,
                    'delete'   => true,
                    'rowCount' => 3,
                    'columns'  => [
                        [
                            'name'    => 'LabelWeeklyScheduleAction',
                            'caption' => 'Wochenplan - Licht An',
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'  => 'UseSettings',
                            'label' => 'Aktiviert',
                            'width' => '100px',
                            'add'   => true,
                            'edit'  => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'Brightness',
                            'caption' => 'Helligkeit',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' %',
                                'minimum' => 0,
                                'maximum' => 100
                            ]
                        ],
                        [
                            'name'    => 'UpdateLastBrightness',
                            'caption' => 'Letzte Helligkeit anpassen',
                            'add'     => false,
                            'width'   => '220px',
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'ExecutionDelay',
                            'caption' => 'Verzögerung',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' s',
                                'minimum' => 0,
                                'maximum' => 10
                            ]
                        ],
                        [
                            'name'    => 'LabelSwitchingConditions',
                            'caption' => "\nBedingungen:",
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'    => 'CheckAutomaticMode',
                            'caption' => 'Automatik',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckSleepMode',
                            'caption' => 'Ruhe-Modus',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckLightMode',
                            'caption' => 'Licht',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Timer',
                                        'value'   => 2
                                    ],
                                    [
                                        'caption' => 'Timer - An',
                                        'value'   => 3
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 4
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckIsDay',
                            'caption' => 'Ist es Tag',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Es ist Nacht',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Es ist Tag',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckTwilight',
                            'caption' => 'Dämmerung',
                            'width'   => '200px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Es ist Tag',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Es ist Nacht',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckPresence',
                            'caption' => 'Anwesenheit',
                            'width'   => '200px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Abwesenheit',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Anwesenheit',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        //Is day
        $isDayVariable = $this->ReadPropertyInteger('IsDay');
        $enableIsDayVariableButton = false;
        if ($isDayVariable > 1 && @IPS_ObjectExists($isDayVariable)) {
            $enableIsDayVariableButton = true;
        }

        $form['elements'][] = [
            'type'     => 'ExpansionPanel',
            'name'     => 'Panel8',
            'caption'  => 'Ist es Tag',
            'expanded' => false,
            'items'    => [
                [
                    'type'    => 'Label',
                    'caption' => 'Bitte wählen Sie die Variable für Ist es Tag aus:'
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'     => 'SelectVariable',
                            'name'     => 'IsDay',
                            'caption'  => 'Ist es Tag',
                            'width'    => '600px',
                            'onChange' => self::MODULE_PREFIX . '_ModifyButton($id, "IsDayVariableConfigurationButton", "ID " . $IsDay . " bearbeiten", $IsDay);'
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'IsDayVariableConfigurationButton',
                            'caption'  => 'ID ' . $isDayVariable . ' bearbeiten',
                            'visible'  => $enableIsDayVariableButton,
                            'objectID' => $isDayVariable
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Es ist Tag',
                    'bold'    => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'DayAction',
                    'caption'  => 'Aktionen',
                    'add'      => true,
                    'delete'   => true,
                    'rowCount' => 3,
                    'columns'  => [
                        [
                            'name'    => 'LabelDayAction',
                            'caption' => 'Ist es Tag - Es ist Tag:',
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'  => 'UseSettings',
                            'label' => 'Aktiviert',
                            'width' => '100px',
                            'add'   => true,
                            'edit'  => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'Brightness',
                            'caption' => 'Helligkeit',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' %',
                                'minimum' => 0,
                                'maximum' => 100
                            ]
                        ],
                        [
                            'name'    => 'UpdateLastBrightness',
                            'caption' => 'Letzte Helligkeit anpassen',
                            'add'     => false,
                            'width'   => '220px',
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'ExecutionDelay',
                            'caption' => 'Verzögerung',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' s',
                                'minimum' => 0,
                                'maximum' => 10
                            ]
                        ],
                        [
                            'name'    => 'LabelSwitchingConditions',
                            'caption' => "\nBedingungen:",
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'    => 'CheckAutomaticMode',
                            'caption' => 'Automatik',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckSleepMode',
                            'caption' => 'Ruhe-Modus',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckLightMode',
                            'caption' => 'Licht',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Timer',
                                        'value'   => 2
                                    ],
                                    [
                                        'caption' => 'Timer - An',
                                        'value'   => 3
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 4
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckTwilight',
                            'caption' => 'Dämmerung',
                            'width'   => '200px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Es ist Tag',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Es ist Nacht',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckPresence',
                            'caption' => 'Anwesenheit',
                            'width'   => '200px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Abwesenheit',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Anwesenheit',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Es ist Nacht',
                    'bold'    => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'NightAction',
                    'caption'  => 'Aktionen',
                    'add'      => true,
                    'delete'   => true,
                    'rowCount' => 3,
                    'columns'  => [
                        [
                            'name'    => 'LabelNightAction',
                            'caption' => 'Ist es Tag - Es ist Nacht:',
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'  => 'UseSettings',
                            'label' => 'Aktiviert',
                            'width' => '100px',
                            'add'   => true,
                            'edit'  => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'Brightness',
                            'caption' => 'Helligkeit',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' %',
                                'minimum' => 0,
                                'maximum' => 100
                            ]
                        ],
                        [
                            'name'    => 'UpdateLastBrightness',
                            'caption' => 'Letzte Helligkeit anpassen',
                            'add'     => false,
                            'width'   => '220px',
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'ExecutionDelay',
                            'caption' => 'Verzögerung',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' s',
                                'minimum' => 0,
                                'maximum' => 10
                            ]
                        ],
                        [
                            'name'    => 'LabelSwitchingConditions',
                            'caption' => "\nBedingungen:",
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'    => 'CheckAutomaticMode',
                            'caption' => 'Automatik',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckSleepMode',
                            'caption' => 'Ruhe-Modus',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckLightMode',
                            'caption' => 'Licht',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Timer',
                                        'value'   => 2
                                    ],
                                    [
                                        'caption' => 'Timer - An',
                                        'value'   => 3
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 4
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckTwilight',
                            'caption' => 'Dämmerung',
                            'width'   => '200px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Es ist Tag',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Es ist Nacht',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckPresence',
                            'caption' => 'Anwesenheit',
                            'width'   => '200px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Abwesenheit',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Anwesenheit',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        //Twilight
        $twilightVariable = $this->ReadPropertyInteger('Twilight');
        $enableTwilightVariableButton = false;
        if ($twilightVariable > 1 && @IPS_ObjectExists($twilightVariable)) {
            $enableTwilightVariableButton = true;
        }

        $form['elements'][] = [
            'type'     => 'ExpansionPanel',
            'name'     => 'Panel9',
            'caption'  => 'Dämmerung',
            'expanded' => false,
            'items'    => [
                [
                    'type'    => 'Label',
                    'caption' => 'Bitte wählen Sie die Variable für die Dämmerung aus:'
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'     => 'SelectVariable',
                            'name'     => 'Twilight',
                            'caption'  => 'Dämmerung',
                            'width'    => '600px',
                            'onChange' => self::MODULE_PREFIX . '_ModifyButton($id, "TwilightVariableConfigurationButton", "ID " . $Twilight . " bearbeiten", $Twilight);'
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'TwilightVariableConfigurationButton',
                            'caption'  => 'ID ' . $twilightVariable . ' bearbeiten',
                            'visible'  => $enableTwilightVariableButton,
                            'objectID' => $twilightVariable
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Es ist Tag',
                    'bold'    => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'TwilightDayAction',
                    'caption'  => 'Aktionen',
                    'add'      => true,
                    'delete'   => true,
                    'rowCount' => 3,
                    'columns'  => [
                        [
                            'name'    => 'LabelTwilightDayAction',
                            'caption' => 'Dämmerung - Es ist Tag:',
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'  => 'UseSettings',
                            'label' => 'Aktiviert',
                            'width' => '100px',
                            'add'   => true,
                            'edit'  => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'Brightness',
                            'caption' => 'Helligkeit',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' %',
                                'minimum' => 0,
                                'maximum' => 100
                            ]
                        ],
                        [
                            'name'    => 'UpdateLastBrightness',
                            'caption' => 'Letzte Helligkeit anpassen',
                            'add'     => false,
                            'width'   => '220px',
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'ExecutionDelay',
                            'caption' => 'Verzögerung',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' s',
                                'minimum' => 0,
                                'maximum' => 10
                            ]
                        ],
                        [
                            'name'    => 'LabelSwitchingConditions',
                            'caption' => "\nBedingungen:",
                            'width'   => '150px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'    => 'CheckAutomaticMode',
                            'caption' => 'Automatik',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckSleepMode',
                            'caption' => 'Ruhe-Modus',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckLightMode',
                            'caption' => 'Licht',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Timer',
                                        'value'   => 2
                                    ],
                                    [
                                        'caption' => 'Timer - An',
                                        'value'   => 3
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 4
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckIsDay',
                            'caption' => 'Ist es Tag',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Es ist Nacht',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Es ist Tag',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckPresence',
                            'caption' => 'Anwesenheit',
                            'width'   => '200px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Abwesenheit',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Anwesenheit',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Es ist Nacht',
                    'bold'    => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'TwilightNightAction',
                    'caption'  => 'Aktionen',
                    'add'      => true,
                    'delete'   => true,
                    'rowCount' => 3,
                    'columns'  => [
                        [
                            'name'    => 'LabelTwilightNightAction',
                            'caption' => 'Dämmerung - Es ist Nacht:',
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'  => 'UseSettings',
                            'label' => 'Aktiviert',
                            'width' => '100px',
                            'add'   => true,
                            'edit'  => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'Brightness',
                            'caption' => 'Helligkeit',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' %',
                                'minimum' => 0,
                                'maximum' => 100
                            ]
                        ],
                        [
                            'name'    => 'UpdateLastBrightness',
                            'caption' => 'Letzte Helligkeit anpassen',
                            'add'     => false,
                            'width'   => '220px',
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'ExecutionDelay',
                            'caption' => 'Verzögerung',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' s',
                                'minimum' => 0,
                                'maximum' => 10
                            ]
                        ],
                        [
                            'name'    => 'LabelSwitchingConditions',
                            'caption' => "\nBedingungen:",
                            'width'   => '150px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'    => 'CheckAutomaticMode',
                            'caption' => 'Automatik',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckSleepMode',
                            'caption' => 'Ruhe-Modus',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckLightMode',
                            'caption' => 'Licht',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Timer',
                                        'value'   => 2
                                    ],
                                    [
                                        'caption' => 'Timer - An',
                                        'value'   => 3
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 4
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckIsDay',
                            'caption' => 'Ist es Tag',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Es ist Nacht',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Es ist Tag',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckPresence',
                            'caption' => 'Anwesenheit',
                            'width'   => '200px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Abwesenheit',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Anwesenheit',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        //Presence
        $presenceVariable = $this->ReadPropertyInteger('Presence');
        $enablePresenceVariableButton = false;
        if ($presenceVariable > 1 && @IPS_ObjectExists($presenceVariable)) {
            $enablePresenceVariableButton = true;
        }

        $form['elements'][] = [
            'type'     => 'ExpansionPanel',
            'name'     => 'Panel10',
            'caption'  => 'Anwesenheit',
            'expanded' => false,
            'items'    => [
                [
                    'type'    => 'Label',
                    'caption' => 'Bitte wählen Sie die Variable für den Anwesenheit aus:'
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'     => 'SelectVariable',
                            'name'     => 'Presence',
                            'caption'  => 'Anwesenheit',
                            'width'    => '600px',
                            'onChange' => self::MODULE_PREFIX . '_ModifyButton($id, "PresenceVariableConfigurationButton", "ID " . $Presence . " bearbeiten", $Presence);'
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'PresenceVariableConfigurationButton',
                            'caption'  => 'ID ' . $presenceVariable . ' bearbeiten',
                            'visible'  => $enablePresenceVariableButton,
                            'objectID' => $presenceVariable
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Anwesenheit',
                    'bold'    => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'PresenceAction',
                    'caption'  => 'Aktionen',
                    'add'      => true,
                    'delete'   => true,
                    'rowCount' => 3,
                    'columns'  => [
                        [
                            'name'    => 'LabelPresenceAction',
                            'caption' => 'Anwesenheit:',
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'  => 'UseSettings',
                            'label' => 'Aktiviert',
                            'width' => '100px',
                            'add'   => true,
                            'edit'  => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'Brightness',
                            'caption' => 'Helligkeit',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' %',
                                'minimum' => 0,
                                'maximum' => 100
                            ]
                        ],
                        [
                            'name'    => 'UpdateLastBrightness',
                            'caption' => 'Letzte Helligkeit anpassen',
                            'add'     => false,
                            'width'   => '220px',
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'ExecutionDelay',
                            'caption' => 'Verzögerung',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' s',
                                'minimum' => 0,
                                'maximum' => 10
                            ]
                        ],
                        [
                            'name'    => 'LabelSwitchingConditions',
                            'caption' => "\nBedingungen:",
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'    => 'CheckAutomaticMode',
                            'caption' => 'Automatik',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckSleepMode',
                            'caption' => 'Ruhe-Modus',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckLightMode',
                            'caption' => 'Licht',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Timer',
                                        'value'   => 2
                                    ],
                                    [
                                        'caption' => 'Timer - An',
                                        'value'   => 3
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 4
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckIsDay',
                            'caption' => 'Ist es Tag',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Es ist Nacht',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Es ist Tag',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckTwilight',
                            'caption' => 'Dämmerung',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Es ist Tag',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Es ist Nacht',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Abwesenheit',
                    'bold'    => true
                ],
                [
                    'type'     => 'List',
                    'name'     => 'AbsenceAction',
                    'caption'  => 'Aktionen',
                    'add'      => true,
                    'delete'   => true,
                    'rowCount' => 3,
                    'columns'  => [
                        [
                            'name'    => 'LabelAbsenceAction',
                            'caption' => 'Abwesenheit:',
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'  => 'UseSettings',
                            'label' => 'Aktiviert',
                            'width' => '100px',
                            'add'   => true,
                            'edit'  => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'Brightness',
                            'caption' => 'Helligkeit',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' %',
                                'minimum' => 0,
                                'maximum' => 100
                            ]
                        ],
                        [
                            'name'    => 'UpdateLastBrightness',
                            'caption' => 'Letzte Helligkeit anpassen',
                            'add'     => false,
                            'width'   => '220px',
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'ExecutionDelay',
                            'caption' => 'Verzögerung',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' s',
                                'minimum' => 0,
                                'maximum' => 10
                            ]
                        ],
                        [
                            'name'    => 'LabelSwitchingConditions',
                            'caption' => "\nBedingungen:",
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'    => 'CheckAutomaticMode',
                            'caption' => 'Automatik',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckSleepMode',
                            'caption' => 'Ruhe-Modus',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckLightMode',
                            'caption' => 'Licht',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Timer',
                                        'value'   => 2
                                    ],
                                    [
                                        'caption' => 'Timer - An',
                                        'value'   => 3
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 4
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckIsDay',
                            'caption' => 'Ist es Tag',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Es ist Nacht',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Es ist Tag',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckTwilight',
                            'caption' => 'Dämmerung',
                            'width'   => '150px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Es ist Tag',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Es ist Nacht',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        //Triggers
        $triggerListValues = [];
        $variables = json_decode($this->ReadPropertyString('Triggers'), true);
        foreach ($variables as $variable) {
            $variableID = 0;
            $variableName = '';
            if ($variable['PrimaryCondition'] != '') {
                $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                if (array_key_exists(0, $primaryCondition)) {
                    if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                        $variableID = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                        $variableName = IPS_GetLocation($variableID); //IPS_GetName($variableID);
                    }
                }
            }
            //Check conditions first
            $conditions = true;
            if ($variableID <= 1 || !@IPS_ObjectExists($variableID)) {
                $conditions = false;
            }
            if ($variable['SecondaryCondition'] != '') {
                $secondaryConditions = json_decode($variable['SecondaryCondition'], true);
                if (array_key_exists(0, $secondaryConditions)) {
                    if (array_key_exists('rules', $secondaryConditions[0])) {
                        $rules = $secondaryConditions[0]['rules']['variable'];
                        foreach ($rules as $rule) {
                            if (array_key_exists('variableID', $rule)) {
                                $id = $rule['variableID'];
                                if ($id <= 1 || !@IPS_ObjectExists($id)) {
                                    $conditions = false;
                                }
                            }
                        }
                    }
                }
            }
            $stateName = 'fehlerhaft';
            $rowColor = '#FFC0C0'; //red
            if ($conditions) {
                $stateName = 'Bedingung nicht erfüllt!';
                $rowColor = '#C0C0FF'; //violett
                if (IPS_IsConditionPassing($variable['PrimaryCondition']) && IPS_IsConditionPassing($variable['SecondaryCondition'])) {
                    $stateName = 'Bedingung erfüllt';
                    $rowColor = '#C0FFC0'; //light green
                }
                if (!$variable['Use']) {
                    $stateName = 'Deaktiviert';
                    $rowColor = '#DFDFDF'; //grey
                }
            }
            $triggerListValues[] = ['ActualStatus' => $stateName, 'TriggerID' => $variableID, 'TriggerName' => $variableName, 'rowColor' => $rowColor];
        }

        $form['elements'][] = [
            'type'     => 'ExpansionPanel',
            'name'     => 'Panel11',
            'caption'  => 'Auslöser',
            'expanded' => false,
            'items'    => [
                [
                    'type'    => 'Label',
                    'caption' => 'Bitte fügen Sie die Auslöser hinzu:'
                ],
                [
                    'type'     => 'List',
                    'name'     => 'Triggers',
                    'caption'  => 'Aktionen',
                    'rowCount' => 5,
                    'add'      => true,
                    'delete'   => true,
                    'sort'     => [
                        'column'    => 'TriggerID',
                        'direction' => 'ascending'
                    ],
                    'columns' => [
                        [
                            'name'    => 'LabelDescription',
                            'caption' => 'Auslöser:',
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'    => 'Use',
                            'caption' => 'Aktiviert',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'ActualStatus',
                            'caption' => 'Aktueller Status',
                            'width'   => '200px',
                            'add'     => ''
                        ],
                        [
                            'caption' => 'ID',
                            'name'    => 'TriggerID',
                            'onClick' => self::MODULE_PREFIX . '_ModifyTriggerListButton($id, "TriggerListConfigurationButton", $Triggers["PrimaryCondition"]);',
                            'width'   => '100px',
                            'add'     => ''
                        ],
                        [
                            'caption' => 'Auslöser',
                            'name'    => 'TriggerName',
                            'onClick' => self::MODULE_PREFIX . '_ModifyTriggerListButton($id, "TriggerListConfigurationButton", $Triggers["PrimaryCondition"]);',
                            'width'   => '300px',
                            'add'     => ''
                        ],
                        [
                            'name'    => 'Description',
                            'caption' => 'Bezeichnung',
                            'width'   => '300px',
                            'add'     => '',
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'SpacerPrimaryCondition',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Auslösebedingung:',
                            'name'    => 'LabelPrimaryCondition',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'italic' => true,
                                'bold'   => true
                            ]
                        ],
                        [
                            'caption' => 'Mehrfachauslösung',
                            'name'    => 'UseMultipleAlerts',
                            'width'   => '180px',
                            'add'     => false,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'PrimaryCondition',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectCondition'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'SpacerSecondaryCondition',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'caption' => 'Weitere Auslösebedingung(en):',
                            'name'    => 'LabelSecondaryCondition',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'   => 'Label',
                                'italic' => true,
                                'bold'   => true
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'SecondaryCondition',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'  => 'SelectCondition',
                                'multi' => true
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'SpacerAdditionalConditions',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'name'    => 'SwitchingConditions',
                            'caption' => "\nWeitere Bedingungen:",
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'    => 'CheckAutomaticMode',
                            'caption' => 'Automatik',
                            'width'   => '150px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckSleepMode',
                            'caption' => 'Ruhe-Modus',
                            'width'   => '150px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckLightMode',
                            'caption' => 'Licht',
                            'width'   => '150px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Aus',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Timer',
                                        'value'   => 2
                                    ],
                                    [
                                        'caption' => 'Timer - An',
                                        'value'   => 3
                                    ],
                                    [
                                        'caption' => 'An',
                                        'value'   => 4
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckIsDay',
                            'caption' => 'Ist es Tag',
                            'width'   => '150px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Es ist Nacht',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Es ist Tag',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckTwilight',
                            'caption' => 'Dämmerung',
                            'width'   => '200px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Es ist Tag',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Es ist Nacht',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'CheckPresence',
                            'caption' => 'Anwesenheit',
                            'width'   => '200px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Keine Prüfung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Abwesenheit',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Anwesenheit',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'ExecutionTimeAfter',
                            'caption' => 'Ausführung nach',
                            'width'   => '150px',
                            'add'     => '{"hour":0,"minute":0,"second":0}',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectTime'
                            ]
                        ],
                        [
                            'name'    => 'ExecutionTimeBefore',
                            'caption' => 'Ausführung vor',
                            'width'   => '150px',
                            'add'     => '{"hour":0,"minute":0,"second":0}',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'SelectTime'
                            ]
                        ],
                        [
                            'caption' => ' ',
                            'name'    => 'SpacerSwitchingOptions',
                            'width'   => '200px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label'
                            ]
                        ],
                        [
                            'name'    => 'SwitchingOptions',
                            'caption' => "\nSchaltoptionen:",
                            'width'   => '150px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type' => 'Label',
                                'bold' => true
                            ]
                        ],
                        [
                            'name'    => 'Brightness',
                            'caption' => 'Helligkeit',
                            'width'   => '150px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' %',
                                'minimum' => 0,
                                'maximum' => 100
                            ]
                        ],
                        [
                            'name'    => 'UpdateLastBrightness',
                            'caption' => 'Letzte Helligkeit anpassen',
                            'add'     => false,
                            'visible' => false,
                            'width'   => '220px',
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'ExecutionDelay',
                            'caption' => 'Verzögerung',
                            'width'   => '150px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' s',
                                'minimum' => 0,
                                'maximum' => 10
                            ]
                        ],
                        [
                            'name'    => 'DutyCycle',
                            'caption' => 'Timer - Einschaltdauer',
                            'width'   => '200px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type' => 'NumberSpinner'
                            ]
                        ],
                        [
                            'name'    => 'DutyCycleUnit',
                            'caption' => 'Timer - Einheit',
                            'width'   => '150px',
                            'add'     => 0,
                            'visible' => false,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Sekunden',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Minuten',
                                        'value'   => 1
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'values' => $triggerListValues,
                ],
                [
                    'type'     => 'OpenObjectButton',
                    'name'     => 'TriggerListConfigurationButton',
                    'caption'  => 'Bearbeiten',
                    'visible'  => false,
                    'objectID' => 0
                ]
            ]
        ];

        //Command control
        $id = $this->ReadPropertyInteger('CommandControl');
        $enableButton = false;
        if ($id > 1 && @IPS_ObjectExists($id)) {
            $enableButton = true;
        }
        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel12',
            'caption' => 'Ablaufsteuerung',
            'items'   => [
                [
                    'type'    => 'Label',
                    'caption' => 'Bitte wählen Sie die Instanz für die Ablaufsteuerung aus:',
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'     => 'SelectModule',
                            'name'     => 'CommandControl',
                            'caption'  => 'Instanz',
                            'moduleID' => self::ABLAUFSTEUERUNG_MODULE_GUID,
                            'width'    => '600px',
                            'onChange' => self::MODULE_PREFIX . '_ModifyButton($id, "CommandControlConfigurationButton", "ID " . $CommandControl . " konfigurieren", $CommandControl);'
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'caption'  => 'ID ' . $id . ' konfigurieren',
                            'name'     => 'CommandControlConfigurationButton',
                            'visible'  => $enableButton,
                            'objectID' => $id
                        ],
                        [
                            'type'    => 'Button',
                            'caption' => 'Neue Instanz erstellen',
                            'onClick' => self::MODULE_PREFIX . '_CreateCommandControlInstance($id);'
                        ]
                    ]
                ]
            ]
        ];

        //Visualisation
        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'name'    => 'Panel13',
            'caption' => 'Visualisierung',
            'items'   => [
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableActive',
                    'caption' => 'Aktiv'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableAutomaticMode',
                    'caption' => 'Automatik'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableSleepMode',
                    'caption' => 'Ruhe-Modus'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableLightMode',
                    'caption' => 'Licht'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableDimmer',
                    'caption' => 'Helligkeit'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableDimmingPresets',
                    'caption' => 'Helligkeit Voreinstellungen'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableLastBrightness',
                    'caption' => 'Letzte Helligkeit'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableSleepModeTimer',
                    'caption' => 'Ruhe-Modus Timer'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableDutyCycleTimer',
                    'caption' => 'Einschaltdauer bis'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableNextSwitchingTime',
                    'caption' => 'Nächste Schaltzeit'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableSunrise',
                    'caption' => 'Nächster Sonnenaufgang'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableSunset',
                    'caption' => 'Nächster Sonnenuntergang'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableWeeklySchedule',
                    'caption' => 'Nächstes Wochenplanereignis'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableIsDay',
                    'caption' => 'Ist es Tag'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableTwilight',
                    'caption' => 'Dämmerung'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnablePresence',
                    'caption' => 'Anwesenheit'
                ]
            ]
        ];

        ########## Actions

        $form['actions'][] =
            [
                'type'    => 'Button',
                'caption' => 'Aktuelle Wochenplan Aktion anzeigen',
                'onClick' => self::MODULE_PREFIX . '_ShowActualWeeklyScheduleAction(' . $this->InstanceID . ');'
            ];

        $form['actions'][] =
            [
                'type'    => 'PopupButton',
                'caption' => 'Beispielskript erstellen',
                'popup'   => [
                    'caption' => 'Beispielskript wirklich automatisch erstellen?',
                    'items'   => [
                        [
                            'type'    => 'Button',
                            'caption' => 'Erstellen',
                            'onClick' => self::MODULE_PREFIX . '_CreateScriptExample(' . $this->InstanceID . ');',
                        ]
                    ]
                ]
            ];

        $form['actions'][] =
            [
                'type'    => 'Label',
                'caption' => ' '
            ];

        $form['actions'][] =
            [
                'type' => 'TestCenter',
            ];

        $form['actions'][] =
            [
                'type'    => 'Label',
                'caption' => ' '
            ];

        //Registered references
        $registeredReferences = [];
        $references = $this->GetReferenceList();
        foreach ($references as $reference) {
            $name = 'Objekt #' . $reference . ' existiert nicht';
            $rowColor = '#FFC0C0'; //red
            if (@IPS_ObjectExists($reference)) {
                $name = IPS_GetName($reference);
                $rowColor = '#C0FFC0'; //light green
            }
            $registeredReferences[] = [
                'ObjectID' => $reference,
                'Name'     => $name,
                'rowColor' => $rowColor];
        }

        //Registered messages
        $registeredMessages = [];
        $messages = $this->GetMessageList();
        foreach ($messages as $id => $messageID) {
            $name = 'Objekt #' . $id . ' existiert nicht';
            $rowColor = '#FFC0C0'; //red
            if (@IPS_ObjectExists($id)) {
                $name = IPS_GetName($id);
                $rowColor = '#C0FFC0'; //light green
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
            $registeredMessages[] = [
                'ObjectID'           => $id,
                'Name'               => $name,
                'MessageID'          => $messageID,
                'MessageDescription' => $messageDescription,
                'rowColor'           => $rowColor];
        }

        $form['actions'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Entwicklerbereich',
            'items'   => [
                [
                    'type'     => 'List',
                    'caption'  => 'Registrierte Referenzen',
                    'name'     => 'RegisteredReferences',
                    'rowCount' => 10,
                    'sort'     => [
                        'column'    => 'ObjectID',
                        'direction' => 'ascending'
                    ],
                    'columns' => [
                        [
                            'caption' => 'ID',
                            'name'    => 'ObjectID',
                            'width'   => '150px',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "RegisteredReferencesConfigurationButton", "ID " . $RegisteredReferences["ObjectID"] . " aufrufen", $RegisteredReferences["ObjectID"]);'
                        ],
                        [
                            'caption' => 'Name',
                            'name'    => 'Name',
                            'width'   => '300px',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "RegisteredReferencesConfigurationButton", "ID " . $RegisteredReferences["ObjectID"] . " aufrufen", $RegisteredReferences["ObjectID"]);'
                        ]
                    ],
                    'values' => $registeredReferences
                ],
                [
                    'type'     => 'OpenObjectButton',
                    'name'     => 'RegisteredReferencesConfigurationButton',
                    'caption'  => 'Aufrufen',
                    'visible'  => false,
                    'objectID' => 0
                ],
                [
                    'type'     => 'List',
                    'name'     => 'RegisteredMessages',
                    'caption'  => 'Registrierte Nachrichten',
                    'rowCount' => 10,
                    'sort'     => [
                        'column'    => 'ObjectID',
                        'direction' => 'ascending'
                    ],
                    'columns' => [
                        [
                            'caption' => 'ID',
                            'name'    => 'ObjectID',
                            'width'   => '150px',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "RegisteredMessagesConfigurationButton", "ID " . $RegisteredMessages["ObjectID"] . " aufrufen", $RegisteredMessages["ObjectID"]);'
                        ],
                        [
                            'caption' => 'Name',
                            'name'    => 'Name',
                            'width'   => '300px',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "RegisteredMessagesConfigurationButton", "ID " . $RegisteredMessages["ObjectID"] . " aufrufen", $RegisteredMessages["ObjectID"]);'
                        ],
                        [
                            'caption' => 'Nachrichten ID',
                            'name'    => 'MessageID',
                            'width'   => '150px'
                        ],
                        [
                            'caption' => 'Nachrichten Bezeichnung',
                            'name'    => 'MessageDescription',
                            'width'   => '250px'
                        ]
                    ],
                    'values' => $registeredMessages
                ],
                [
                    'type'     => 'OpenObjectButton',
                    'name'     => 'RegisteredMessagesConfigurationButton',
                    'caption'  => 'Aufrufen',
                    'visible'  => false,
                    'objectID' => 0
                ]
            ]
        ];

        //Dummy info message
        $form['actions'][] =
            [
                'type'    => 'PopupAlert',
                'name'    => 'InfoMessage',
                'visible' => false,
                'popup'   => [
                    'closeCaption' => 'OK',
                    'items'        => [
                        [
                            'type'    => 'Label',
                            'name'    => 'InfoMessageLabel',
                            'caption' => '',
                            'visible' => true
                        ]
                    ]
                ]
            ];

        ########## Status

        $form['status'][] = [
            'code'    => 101,
            'icon'    => 'active',
            'caption' => $module['ModuleName'] . ' wird erstellt',
        ];
        $form['status'][] = [
            'code'    => 102,
            'icon'    => 'active',
            'caption' => $module['ModuleName'] . ' ist aktiv',
        ];
        $form['status'][] = [
            'code'    => 103,
            'icon'    => 'active',
            'caption' => $module['ModuleName'] . ' wird gelöscht',
        ];
        $form['status'][] = [
            'code'    => 104,
            'icon'    => 'inactive',
            'caption' => $module['ModuleName'] . ' ist inaktiv',
        ];
        $form['status'][] = [
            'code'    => 200,
            'icon'    => 'inactive',
            'caption' => 'Es ist Fehler aufgetreten, weitere Informationen unter Meldungen, im Log oder Debug!',
        ];

        return json_encode($form);
    }
}