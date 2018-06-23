var brower = require('casper').create({
    proxy: "127.0.0.1:9050",
    "proxy-type": "socks5",
    clientScripts:  [
        '/var/www/html/casperjs/jquery.js'
    ],
    waitTimeout: 10000,
    stepTimeout: 10000,
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
args = brower.cli.args;
use_time  = 0;


brower.thenOpen('https://account.chsi.com.cn/passport/login');


brower.then(function clickLogin(){
    this.fill("form#fm1",{
        'username':args[0],
        'password':args[1]
    },false);

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
    browserinspect = this.evaluate(function(){
        var browserinspect = document.querySelectorAll('#browserinspect').length;
        return browserinspect;
    })
    this.echo('captcha'+captcha);
    if(browserinspect > 0) {
        brower.thenOpen('https://my.chsi.com.cn/archive/gdjy/xj/show.action',function(){
            url22 = this.evaluate(function(){
                var img22 = $("img.xjxx-img").attr('src');
                return img22;
            })
            if(url22) {
                this.captureSelector('/var/www/html/casperjs/chsi/data_'+args[2]+'.png', 'html');
                savestr = '{"status":"login success", "data_url":"/var/www/html/casperjs/chsi/data_'+args[2]+'.png"}';
                var fs = require('fs');
                fs.write('/var/www/html/casperjs/chsi/result_'+args[2]+'.txt', savestr, 'w');
            }

        });
    }
    else if(captcha > 0) {
        this.click('#captcha');
        brower.wait(4000, function () {
            this.captureSelector('/var/www/html/casperjs/chsi/code_'+args[2]+'.png', '#stu_reg_vcode_anchor');
            savestr = '{"status":"need code", "data_url":"/var/www/html/casperjs/chsi/code_'+args[2]+'.png"}';
            var fs = require('fs');
            fs.write('/var/www/html/casperjs/chsi/result_'+args[2]+'.txt', savestr, 'w');
            brower.wait(5000, getCodeAjax);
        });
    }


});

function getCodeAjax(){
    var cookiefile = '/var/www/html/casperjs/chsi/result_'+args[2]+'.txt';
    this.echo(cookiefile);
    var fs = require('fs');
    if(fs.exists(cookiefile)){
        var cookies = JSON.parse(fs.read(cookiefile));
        if(cookies.code){
            this.echo(cookies.code);
            this.echo('get code ajax end');

            brower.then(function clickLogin(){
                this.fill("form#fm1",{
                    'username':args[0],
                    'password':args[1],
                    'captcha':cookies.code
                },false);

                this.click('.btn_login');
            });

            brower.wait(4000, function waitLogin() {
                this.echo("I've waited for 4 second.");
            });

            brower.then(function getcode(){
                browserinspect = this.evaluate(function(){
                    var browserinspect = document.querySelectorAll('#browserinspect').length;
                    return browserinspect;
                })
                this.echo('browserinspect'+browserinspect);
                if(browserinspect > 0) {
                    brower.thenOpen('https://my.chsi.com.cn/archive/gdjy/xj/show.action',function(){
                        url22 = this.evaluate(function(){
                            var img22 = $("img.xjxx-img").attr('src');
                            return img22;
                        })
                        if(url22) {
                            this.captureSelector('/var/www/html/casperjs/chsi/data_'+args[2]+'.png', 'html');
                            savestr = '{"status":"login success", "data_url":"/var/www/html/casperjs/chsi/data_'+args[2]+'.png"}';
                            var fs = require('fs');
                            fs.write('/var/www/html/casperjs/chsi/result_'+args[2]+'.txt', savestr, 'w');
                        }

                    });
                }
                else{
                    savestr = '{"status":"error", "data_url":""}';
                    var fs = require('fs');
                    fs.write('/var/www/html/casperjs/chsi/result_'+args[2]+'.txt', savestr, 'w');
                }
            });

            return;
        }
    }
    use_time += 5;
    if(use_time > 180){
        savestr = '{"status":"error", "data_url":""}';
        var fs = require('fs');
        fs.write('/var/www/html/casperjs/chsi/result_'+args[2]+'.txt', savestr, 'w');
        return;
    }
    this.echo(use_time);
    brower.wait(5000, getCodeAjax);
}


brower.run();