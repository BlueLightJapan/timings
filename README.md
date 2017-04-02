Aikar's Minecraft Timings Viewer (BlueLight フォーク)
=======

 <http://timings.aikar.co/> の旧バージョン、日本語版です。　ｖ１バージョンとなります。
 
 
Installation
======
composer,php,nodejsが必要です
 git clone https://github.com/BlueLightJapan/timings/
 npm rebuild
 php composer.php update

Contributing
======

The main thing holding up Timings v2 in Spigot is this Web UI for it! I don't have time to focus on it, so any help to get it close to usable for general public would be appreciated!

Current Active Contributors:
  - Aikar - Project Owner
  - DemonWav
  - willies952002

Want to contribute? Join #aikar Spigot IRC ([join here](https://irc.spi.gt/iris/?channels=#aikar)), 
and let me know what your interested in working on so we don't have people working on the same thing.

We're currently in the middle of migrating the JS code to Dart. So hold off before working on any JS code.

Setting Up Environment
=====
You need A webserver such as Apache or Nginx, and PHP 5.6.
Apache is preferred incase .htaccess is needed.

Check out repo, copy config.ini to config.dev.ini and edit to your needs.

You'll also need NodeJS v4 LTS

If you are not using Ubuntu 14.04, you may need to "npm rebuild" but i'm unsure if the binary based deps are needed or not.

To compile static resources and monitor them for changes, simply run `gulp` and control + c to abort watching.
Gulp will monitor all files for changes and recompile the css/js for you.

An initial debug data file is included in the project that will automatically load for dev environments.

You may create config.dev.ini to override config options like so:

>trusted_ip="10.0.1.100"  
>custom_security="../security/security.php"  
>dev_id=ae6cfe033ca541f39a0fc52c3b51b2e1


License
======
The project is licensed under MIT as I'm a "I don't care" type of person usually, but I'd really prefer if clones did not start up for no good reason.

Remember: [when to fork](http://jamesdixon.wordpress.com/forking-protocol-why-when-and-how-to-fork-an-open-source-project/).

I'm totally open to any reasonable improvement. So if you think it can be better, talk to me on Spigot or Esper IRC and PR :)

If I disappear from Minecraft, then please keep this tool alive!
