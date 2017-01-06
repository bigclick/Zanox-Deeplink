# Zanox-Deeplink
Zanox Deeplink Script zum automatisiertem erstellen von Deeplinks für das Zanox Affiliate Netzwerk.


Dieses Script nutzt die Zanox-Zugangsdaten um sich in Netzwerk einzuloggen um dann über die URL `http://toolbox.zanox.com/deeplink/` einen Deeplink zu erzeugen.


### Installation
```php
require_once 'class.zanox_api.php';

try {
    $zanoxDeepLink = new ZanoxDeepLink('LOGIN', 'PASSWORD', 'ADSPACE', 'ADVERTISER');
    echo $zanoxDeepLink->getDeeplink('PRODUCT_URL');

} catch (Exception $e) {
    echo 'Error : '.$e->getMessage();
}
```

### Beschreibungen
|Variable|Typ|Erläuterung|
|---|---|---|
|LOGIN|`varchar`|Email-Adresse zum einloggen in das zanox-Netzwerk|
|PASSWORD|`varchar`|Passwort|
|ADSPACE|`integer`|Konto in dem das Partnerprogramm liegt|
|ADVERTISER|`integer` *(optional)*|Sollte ein Programm für mehr als ein Land angemeldet sein|
|PRODUCT_URL|`varchar`|URL aus der ein Deeplink erzeugt werden soll|
