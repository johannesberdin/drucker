# Print mails from everywhere

Printing emails and email attachments is super annoying to me. Despite the question why I still print them out, I need to print some documents caused by German/European law regulations.

I'm on the go most of the time and I really do not want to replace my lovely Brother printing beast with an internet-connected one and because the fact that the printer is already connected to the apptimists HQ server, I decided to write a small mail poll for receiving mails from a dedicated inbox and print them out in a manner I like.

## Setup
- Install fetchmail, e.g. `brew install fetchmail`
- Install PHP mailparse extension, e.g. `brew install php-mailparse`
- Get the repo via `git clone https://github.com/johannesberdin/drucker /opt/drucker`
- Run `composer install`

### Configure fetchmail

Just modify the following lines according to your mail configuration.
```
# /opt/drucker/fetchmail.conf

set no bouncemail
set invisible
#set daemon 43200 # uncomment for polling every 12 hours

poll "<YOUR_POP3_HOST>" protocol pop3:
        username "<YOUR_POP3_USER>" password "<YOUR_POP3_PASSWORD>" is "<YOUR_LOCAL_USER>" here
        mda "/opt/drucker/drucker.php"
        ssl
```
Test your configuration via `fetchmail -L /var/log/fetchmail.log -f /opt/drucker/fetchmail.conf`

![https://giphy.com/embed/yUrUb9fYz6x7a](https://media.giphy.com/media/yUrUb9fYz6x7a/giphy.gif)

## Feature list
- Print all mail attachments
- Print mail thread including original message and forwarded/quoted mails
- Configure allowed senders to prevent empty paper trays
- Use simple mail template

## In development
- Code some test cases
