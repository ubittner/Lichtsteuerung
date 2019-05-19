# Lichtsteuerung

[![Version](https://img.shields.io/badge/Symcon_Version-5.1>-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
![Version](https://img.shields.io/badge/Modul_Version-1.00-blue.svg)
![Version](https://img.shields.io/badge/Modul_Build-1-blue.svg)
![Version](https://img.shields.io/badge/Code-PHP-blue.svg)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)  

![Logo](../imgs/ubs3_logo.png)  

Ein Projekt von Ulrich Bittner - Smart System Solutions  

Dieses Modul schaltet die Beleuchtung mittels [IP-Symcon](https://www.symcon.de) manuell oder autmatisch ein/aus.

Für dieses Modul besteht kein Anspruch auf Fehlerfreiheit, Weiterentwicklung, sonstige Unterstützung oder Support.

Bevor das Modul installiert wird, sollte unbedingt ein Backup von IP-Symcon durchgeführt werden.

Der Entwickler haftet nicht für eventuell auftretende Datenverluste oder sonstige Schäden.

Der Nutzer stimmt den o.a. Bedingungen, sowie den Lizenzbedingungen ausdrücklich zu.

### Inhaltverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

* Steuerung der Beleuchtung (ein/aus)
    * manuelle Steuerung
    * automatisch Steuerung
        * Astrofunktion (z.B. Sonnenaufgang, Sonnenuntergang)
        * Vorgegebene Uhrzeit
        * Zufällige Verzögerung der Ausführungszeit
  
### 2. Voraussetzungen

- IP-Symcon ab Version 5.1

- Es werden nur Variablen mit dem Ident `STATE` geschaltet. 

### 3. Software-Installation

- Sie benötigen vom Entwickler entsprechende Zugangsdaten zur Nutzung des Moduls.  

- Über das Modul-Control folgende URL hinzufügen: `https://git.ubittner.de/ubittner/Lichtsteuerung.git`

### 4. Einrichten der Instanzen in IP-Symcon

- In IP-Symcon an beliebiger Stelle `Instanz hinzufügen` auswählen und `Lichtsteuerung` auswählen, welches unter dem Hersteller `UBS3` aufgeführt ist. Es wird eine Instanz angelegt, in der die Eigenschaften zur Steuerung festgelegt werden können.

__Konfigurationsseite__:

Name                                | Beschreibung
----------------------------------- | ---------------------------------
(0) Instanzinformationen            | Informationen zu der Instanz
(1) Auslöser                        | Konfiguration der Zeiten oder Astrofunktionen als Auslöser
(2) Beleuchtung                     | Konfiguration der verwendeten Beleuchtung
(3) Sicherung/Wiederherstellung     | Sicherung, bzw. Wiederherstellung der Instanzkonfiguration

### 5. Statusvariablen und Profile

Die Statusvariablen werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

##### Statusvariablen

Name                | Typ       | Beschreibung
------------------- | --------- | ----------------
Lights              | Boolean   | Schaltet die Beleuchtung ein/aus
AutomaticMode       | Boolean   | Schaltet den Automatikmodus ein/aus
NextSwitchOnTime    | String    | Zeigt die nächste Einschaltzeit an
NextSwitchOffTime   | String    | Zeigt die nächste Ausschaltzeit an

##### Profile:

Es werden zur Zeit keine zusätzliche Profile hinzugefügt.

### 6. WebFront

Über das WebFront kann die Beleuchtung ein- und ausgeschaltet werden.  
Ebenfalls kann die Automatik de- und aktiviert werden.  
Die nächsten Ein- und Ausschaltzeiten werden angezeigt.

### 7. PHP-Befehlsreferenz

`LS_SwitchLights(integer $InstanzID, bool $Status);`  
Schaltet die Beleuchtung ein/aus.  
`LS_SwitchLights(12345, true);`

`LS_SetAutomaticMode(integer $InstanzID, bool $Status);`  
Schaltet den Automatikmodus ein/aus.  
`LS_SetAutomaticMode(12345, true);`

Weitere Funktionen können im Skripteditor der Vorschlagsliste `LS_` entnommen werden.