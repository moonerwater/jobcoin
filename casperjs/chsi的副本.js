var brower = require('casper').create({
    proxy: "127.0.0.1:9050",
    "proxy-type": "socks5",
    clientScripts:  [
        '/var/www/html/casperjs/jquery.js'
    ],
    waitTimeout: 25000,
    stepTimeout: 25000,
    verbose: true,
    logLevel: "info",  //debug info  error
    viewportSize: {
        width: 1920,
        height: 1080
    },
    pageSettings: {
        "userAgent": 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:45.0) Gecko/20100101 Firefox/45.0',
        "loadImages": true,
        "loadPlugins": true,
        "webSecurityEnabled": false,
        "ignoreSslErrors": true
    },
    onWaitTimeout: function() {
        casper.echo('Wait TimeOut Occured');
    },
    onStepTimeout: function() {
        casper.echo('Step TimeOut Occured');
    }
});

brower.start();
var args = brower.cli.args;



brower.thenOpen('https://account.chsi.com.cn/passport/login',function getCookie(){
    var fs = require('fs');
    var cookiefile = '/var/www/html/casperjs/cookies/chsi_'+args[2]+'.txt';
    if(fs.exists(cookiefile)){
        var cookies = JSON.parse(fs.read(cookiefile));
        this.page.cookies = cookies;
    }
    this.echo(JSON.stringify((this.page.cookies)));
});


brower.then(function setPassword() {
    if(args[3]){
        /*captcha = this.evaluate(function(str){
            captcha = $('#fm1').append('<input type="text" id="captcha" name="captcha" value="'+str+'" />');
            return captcha;
        },args[3]);

        this.fill("form#fm1",{
            'username':args[0],
            'password':args[1]
        },false);


        brower.wait(4000, function waitLogin() {
            this.echo("I've waited for 4 second.");
        });
        brower.then(function cutImg() {
            this.captureSelector('/var/www/html/casperjs/test3.png', 'html');
        });

        brower.then(function clickLogin(){
            this.click('.btn_login');
        });*/
    }
    else{
        this.fill("form#fm1",{
            'username':args[0],
            'password':args[1]
        },false);

        brower.then(function saveCookie(){
            var fs = require('fs');
            var cookies = JSON.stringify((this.page.cookies));
            fs.write('/var/www/html/casperjs/cookies/chsi_'+args[2]+'.txt', cookies, 'w');
            this.echo(JSON.stringify((this.page.cookies)));
        });

        brower.then(function clickLogin(){
            this.click('.btn_login');
        });

        brower.wait(4000, function waitLogin() {
            this.echo("I've waited for 4 second.");
        });

        brower.then(function getcode(){
            captcha = this.evaluate(function(){
                var captcha = document.querySelectorAll('#captcha').length;
                return captcha;
            })
            this.echo(captcha);
            if(captcha > 0) {
                this.click('#captcha');
                brower.wait(4000, function () {
                    this.captureSelector('/var/www/html/casperjs/test2.png', '#stu_reg_vcode_anchor');
                });
            }

        });

    }

});




brower.thenOpen('https://my.chsi.com.cn/archive/gdjy/xj/show.action',function(){
    url22 = this.evaluate(function(){
        var img22 = $("img.xjxx-img").attr('src');
        return img22;
    })
    this.echo(url22);
});



brower.wait(4000, function waitLogin() {
    this.echo("I've waited for 4 second.");
});



brower.then(function cutImg() {
    this.captureSelector('/var/www/html/casperjs/test.png', 'html');
});






/*var url = '/var/www/html/casperjs/chsi/1.png'
brower.then(function cutImg() {
    this.captureSelector(url, 'html');
    this.echo(url);
});*/




/*var args = brower.cli.args;

brower.thenOpen('https://account.chsi.com.cn/passport/login');

brower.then(function setPassword() {
    this.fill("form#fm1",{
        'username':args[0],
        'password':args[1]
    },false);
});

brower.then(function clickLogin(){
    this.click('.btn_login');
});

brower.wait(4000, function waitLogin() {
    this.echo("I've waited for 4 second.");
});

var url = '/var/www/html/casperjs/chsi/'+args[2]+'.png'
brower.then(function cutImg() {
    this.captureSelector(url, 'html');
    this.echo(url);
});*/

/*brower.waitFor(function waitLogin() {
    return this.evaluate(function() {
        return document.querySelectorAll('#browserinspect').length > 0;
    });
}, function then() {
    this.echo('login success');
});

brower.thenOpen('https://my.chsi.com.cn/archive/gdjy/xj/show.action',function(){
    url22 = this.evaluate(function(){
        var img22 = $("img.xjxx-img").attr('src');
        return img22;
    })
    //this.echo(url22);
});

var url = '/var/www/html/casperjs/chsi/'+args[2]+'.png'
brower.then(function cutImg() {
    this.captureSelector(url, 'html');
    this.echo(url);
});*/


/*brower.wait(4000, function waitLogin() {
    this.echo("I've waited for 4 second.");
});

brower.then(function cutImg() {
    this.captureSelector('/var/www/html/casperjs/test.png', 'html');
});*/

brower.run();