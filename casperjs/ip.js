var casper = require('casper').create({
    verbose: true,
    logLevel: "info"
});

casper.on('remote.message', function(msg) {
    this.echo('remote message caught: ' + msg);
});

casper.start("https://whatismyipaddress.com/", function() {
    this.evaluate(function () {
        console.log('current ip is: '+document.querySelector('#section_left').textContent.trim());
    });
});

casper.run(); 