# <h1 align="center">NimblePHP - Email</h1>

Pakiet dostarcza proste i elastyczne narzędzie do wysyłania wiadomości email w aplikacjach PHP. Biblioteka umożliwia łatwą konfigurację i obsługę różnych dostawców poczty elektronicznej, w tym Gmail, Outlook, SendGrid i innych.

**Dokumentacja** projektu dostępna jest pod linkiem:
https://nimblemvc.github.io/documentation/extension/email/start/#

## Instalacja

```shell
composer require nimblephp/email
```

## Funkcje

- Modułowa architektura z możliwością rozszerzania
- Obsługa różnych dostawców email (Gmail, Outlook, Yahoo, Zoho, SendGrid, Mailgun, Office365, Mailtrap, Amazon SES)
- Konfiguracja z wykorzystaniem zmiennych środowiskowych
- Obsługa HTML i załączników
- Wbudowane obrazki w treści HTML
- Wsparcie dla odbiorców CC i BCC
- Możliwość masowego dodawania odbiorców
- Obsługa szablonów emaili z podstawianiem zmiennych
- Załączniki tworzone z ciągów znaków (bez plików)
- Dodawanie niestandardowych nagłówków
- Obsługa TLS/SSL
- Wsparcie dla uwierzytelniania OAuth2 (wymagane dla Outlook/Office365)
- Konfigurowalne limity czasu połączeń
- Funkcje logowania
- Możliwość wymiany transportu (SMTP, PHP mail() i inne)
- Wsparcie dla wstrzykiwania zależności

## Konfiguracja

Bibliotekę można skonfigurować za pomocą zmiennych środowiskowych:

```dotenv
# Podstawowa konfiguracja
EMAIL_HOST=smtp.example.com
EMAIL_PORT=587
EMAIL_USERNAME=user@example.com
EMAIL_PASSWORD=haslo
EMAIL_AUTH=true
EMAIL_SECURE=tls
EMAIL_FROM=nadawca@example.com
EMAIL_FROM_NAME="Nazwa Nadawcy"

# Lub użyj predefiniowanej konfiguracji
EMAIL_CONFIG=SENDGRID
EMAIL_USERNAME=apikey
EMAIL_PASSWORD=twoj_klucz_api

# Dla Outlook/Office365 (wymaga OAuth2)
EMAIL_CONFIG=OUTLOOK
EMAIL_USERNAME=twoj@outlook.com
EMAIL_OAUTH_TOKEN=twoj_token_oauth2

# Dla Amazon SES
EMAIL_CONFIG=AMAZON_SES
EMAIL_USERNAME=twoj_uzytkownik_ses
EMAIL_PASSWORD=twoj_klucz_ses
SES_ENDPOINT=email-smtp.us-east-1.amazonaws.com
```

## Struktura biblioteki

```
src/
├── Config/
│   └── EmailConfig.php
├── Transport/
│   ├── TransportInterface.php
│   ├── SmtpTransport.php
│   └── PhpMailTransport.php
├── Template/
│   └── TemplateProcessor.php
├── Exception/
│   └── EmailException.php
└── Email.php
```

## Przykłady użycia

### Podstawowe wysyłanie emaila

```php
use NimblePHP\Email\Email;

// Użycie domyślnej konfiguracji (z zmiennych środowiskowych)
$email = new Email();
$email->to('odbiorca@example.com')
      ->from('nadawca@example.com', 'Jan Kowalski')
      ->subject('Ważna wiadomość')
      ->body('Treść wiadomości', false)
      ->send();
```

### Wysyłanie emaila HTML z załącznikiem

```php
$email = new Email();
$email->to('odbiorca@example.com')
      ->from('nadawca@example.com', 'Jan Kowalski')
      ->subject('Ważna wiadomość')
      ->body('<h1>Witaj!</h1><p>To jest wiadomość HTML.</p>', true)
      ->attachment('/sciezka/do/pliku.pdf', 'dokument.pdf')
      ->send();
```

### Dodawanie odbiorców CC i BCC

```php
$email = new Email();
$email->to('odbiorca@example.com')
      ->from('nadawca@example.com')
      ->subject('Ważna wiadomość')
      ->body('Treść wiadomości')
      ->cc('kopia@example.com')
      ->bcc('ukryta-kopia@example.com')
      ->send();
```

### Masowe dodawanie odbiorców

```php
$email = new Email();
$email->from('nadawca@example.com')
      ->subject('Ważna wiadomość grupowa')
      ->body('Treść wiadomości')
      ->addRecipients([
          'odbiorca1@example.com' => 'Jan Kowalski',
          'odbiorca2@example.com' => 'Anna Nowak',
          'odbiorca3@example.com'
      ])
      ->addCc([
          'kopia1@example.com' => 'Piotr Wiśniewski',
          'kopia2@example.com'
      ])
      ->send();
```

### Używanie szablonu emaila

```php
$email = new Email();
$email->to('odbiorca@example.com')
      ->from('nadawca@example.com')
      ->subject('Witaj w naszym serwisie')
      ->template('/sciezka/do/szablonu.html', [
          'imie' => 'Jan',
          'data' => date('Y-m-d'),
          'link_aktywacyjny' => 'https://example.com/activate?token=123'
      ])
      ->send();
```

### Użycie szablonu jako ciąg znaków

```php
$templateContent = '<h1>Witaj {{imie}}!</h1><p>Dziękujemy za rejestrację w dniu {{data}}.</p>';

$email = new Email();
$email->to('odbiorca@example.com')
      ->from('nadawca@example.com')
      ->subject('Witaj w naszym serwisie')
      ->templateFromString($templateContent, [
          'imie' => 'Jan',
          'data' => date('Y-m-d')
      ])
      ->send();
```

### Osadzanie obrazków w treści HTML

```php
$email = new Email();
$email->to('odbiorca@example.com')
      ->from('nadawca@example.com')
      ->subject('Wiadomość z obrazkiem')
      ->body('<h1>Witaj!</h1><p>Oto nasze logo:</p><img src="cid:logo" alt="Logo">', true)
      ->embedImage('/sciezka/do/logo.png', 'logo')
      ->send();
```

### Załącznik z ciągu znaków

```php
$pdf = generuj_pdf(); // Funkcja generująca zawartość PDF

$email = new Email();
$email->to('odbiorca@example.com')
      ->from('nadawca@example.com')
      ->subject('Raport miesięczny')
      ->body('W załączniku znajduje się raport za bieżący miesiąc.')
      ->attachmentFromString($pdf, 'raport.pdf', 'application/pdf')
      ->send();
```

### Dodawanie niestandardowych nagłówków

```php
$email = new Email();
$email->to('odbiorca@example.com')
      ->from('nadawca@example.com')
      ->subject('Ważna wiadomość')
      ->body('Treść wiadomości')
      ->addHeader('X-Priority', '1')
      ->addHeader('X-Mailer', 'NimblePHP')
      ->send();
```

### Używanie OAuth2 dla Outlook/Office365

```php
$email = new Email();
$email->to('odbiorca@example.com')
      ->from('twoj@outlook.com', 'Twoje Imię')
      ->subject('Ważna wiadomość')
      ->body('Treść wiadomości')
      ->setOAuthToken('twoj_token_oauth2')
      ->send();
```

### Ustawianie limitów czasu

```php
$email = new Email();
$email->to('odbiorca@example.com')
      ->from('nadawca@example.com')
      ->subject('Ważna wiadomość')
      ->body('Treść wiadomości')
      ->setConnectionTimeout(60)  // 60 sekund na nawiązanie połączenia
      ->setTimeout(120)          // 120 sekund na operacje
      ->send();
```

### Niestandardowa konfiguracja

```php
use NimblePHP\Email\Email;
use NimblePHP\Email\Config\EmailConfig;

// Utworzenie własnej konfiguracji
$config = new EmailConfig();

// Ustawienie konfiguracji bezpośrednio
$config->setConfig([
    'host' => 'moj-serwer-smtp.com',
    'port' => 465,
    'username' => 'uzytkownik',
    'password' => 'haslo',
    'auth' => true,
    'secure' => 'ssl'
]);

$email = new Email($config);
$email->to('odbiorca@example.com')
      ->subject('Testowa wiadomość')
      ->body('To jest wiadomość testowa')
      ->send();
```

### Niestandardowy transport

```php
use NimblePHP\Email\Email;
use NimblePHP\Email\Config\EmailConfig;
use NimblePHP\Email\Transport\SmtpTransport;

// Utworzenie własnej konfiguracji i transportu
$config = new EmailConfig();
$transport = new SmtpTransport($config);
$transport->setConnectionTimeout(60);

$email = new Email($config, $transport);
$email->to('odbiorca@example.com')
      ->subject('Wiadomość przez niestandardowy transport')
      ->body('Treść wiadomości')
      ->send();
```

### Niestandardowy procesor szablonów

```php
use NimblePHP\Email\Email;
use NimblePHP\Email\Template\TemplateProcessor;

// Użycie z niestandardowym procesorem szablonów
$templateProcessor = new TemplateProcessor();
$templateProcessor->setPlaceholderFormat('${%s}'); // zmiana formatu placeholderów na ${nazwa}

$email = new Email(null, null, $templateProcessor);
$email->to('odbiorca@example.com')
      ->from('nadawca@example.com')
      ->subject('Niestandardowy format szablonu')
      ->templateFromString('<p>Witaj ${imie}!</p>', ['imie' => 'Jan'])
      ->send();
```

### Kompletny przykład użycia zaawansowanych funkcji

```php
use NimblePHP\Email\Email;
use NimblePHP\Email\Config\EmailConfig;
use NimblePHP\Email\Transport\SmtpTransport;
use NimblePHP\Email\Template\TemplateProcessor;

// Pełna konfiguracja z wszystkimi komponentami
$config = new EmailConfig();
$config->setConfig([
    'host' => 'smtp.example.com',
    'port' => 587,
    'username' => 'user',
    'password' => 'pass',
    'auth' => true,
    'secure' => 'tls'
]);

$transport = new SmtpTransport($config);
$transport->setConnectionTimeout(30);
$transport->setTimeout(60);

$templateProcessor = new TemplateProcessor();

$email = new Email($config, $transport, $templateProcessor);
$email->to('odbiorca@example.com', 'Jan Kowalski')
      ->from('nadawca@example.com', 'System Powiadomień')
      ->addCc([
          'kopia1@example.com' => 'Osoba 1',
          'kopia2@example.com' => 'Osoba 2'
      ])
      ->addBcc(['ukryta-kopia@example.com'])
      ->subject('Raport miesięczny')
      ->addHeader('X-Priority', '1')
      ->templateFromString('<h1>Raport za {{miesiac}}</h1><p>Znajdziesz go w załączniku.</p>', [
          'miesiac' => 'kwiecień 2025'
      ], true)
      ->attachment('/sciezka/do/raportu.pdf')
      ->embedImage('/sciezka/do/logo.png', 'logo_id')
      ->send();
```

## Wsparcie dla dostawców poczty

Biblioteka zapewnia predefiniowane konfiguracje dla popularnych dostawców poczty:

- Gmail (`EMAIL_CONFIG=GMAIL`)
- Outlook/Office365 (`EMAIL_CONFIG=OUTLOOK` lub `EMAIL_CONFIG=OFFICE365`)
- Yahoo (`EMAIL_CONFIG=YAHOO`)
- Zoho (`EMAIL_CONFIG=ZOHO`)
- SendGrid (`EMAIL_CONFIG=SENDGRID`)
- Mailgun (`EMAIL_CONFIG=MAILGUN`)
- Mailtrap (`EMAIL_CONFIG=MAILTRAP`) - do testowania
- Amazon SES (`EMAIL_CONFIG=AMAZON_SES`)

## Rozszerzanie biblioteki

Biblioteka została zaprojektowana z myślą o łatwym rozszerzaniu:

1. **Dodawanie nowych transportów**:
    - Stwórz nową klasę implementującą `TransportInterface`
    - Przekaż ją do `Email` przez konstruktor lub metodę `setTransport()`

2. **Niestandardowe przetwarzanie szablonów**:
    - Rozszerz klasę `TemplateProcessor` lub utwórz własną implementację
    - Przekaż ją do `Email` przez konstruktor lub metodę `setTemplateProcessor()`

3. **Alternatywne źródła konfiguracji**:
    - Rozszerz klasę `EmailConfig` dla obsługi innych źródeł konfiguracji (np. plik JSON, baza danych)

## Współtworzenie

Zachęcamy do współtworzenia! Masz sugestie, znalazłeś błędy, chcesz pomóc w rozwoju? Otwórz issue lub prześlij pull request.

## Pomoc

Wszelkie problemy oraz pytania należy zadawać przez zakładkę discussions w github pod linkiem:
https://github.com/NimbleMVC/Email/discussions