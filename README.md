hwop-engine
===========

Connecting components to a markup engine.

Don't forget to initialize submodules!

```
git submodule init && git submodule update
```

Following packages are dependencies on debian: php5-cli php5-curl php5-apc graphviz

Pygments!

```
sudo apt-get install python-setuptools
sudo easy_install pygments
```

Download phantomjs binary and put it in path, before you call 'hwop'.

http://phantomjs.org/download.html : version at the time of the writing is
1.9.7, and links are:

https://bitbucket.org/ariya/phantomjs/downloads/phantomjs-1.9.7-linux-x86_64.tar.bz2

https://bitbucket.org/ariya/phantomjs/downloads/phantomjs-1.9.7-linux-i686.tar.bz2

When all is ready, test your engine with

```
./hwop test
```

And finally, build something, i'm giving up.

```
./hwop build --src=example --dst=htdocs
```
