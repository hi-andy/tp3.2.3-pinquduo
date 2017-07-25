var conn = new WebIM.connection({
    https: WebIM.config.https,
    url: WebIM.config.xmppURL,
    isAutoLogin: WebIM.config.isAutoLogin,
    isMultiLoginSessions: WebIM.config.isMultiLoginSessions
});
//消息数量
var msgCount = 0;
//    当前聊天对象
var user_id = '';
//    登陆
//    当前聊天用户
var username = '';
//    商户头像
var store_avatar = '';
//    商户名字
var store_username = '';
//    数据结构
var ext = {};
var exts = [];
var flag = true;
//    点击图片放大
function imgClick(e) {
    //        console.log(e.src);
    $('.imgShadow').html('<img src="' + e.src + '">');
    $('.imgShadow').css('display', 'flex');
}
//    时间戳转换
function formatDateTime(inputTime) {
    var date = new Date(inputTime);
    var y = date.getFullYear();
    var m = date.getMonth() + 1;
    m = m < 10 ? ('0' + m) : m;
    var d = date.getDate();
    d = d < 10 ? ('0' + d) : d;
    var h = date.getHours();
    h = h < 10 ? ('0' + h) : h;
    var minute = date.getMinutes();
    var second = date.getSeconds();
    minute = minute < 10 ? ('0' + minute) : minute;
    second = second < 10 ? ('0' + second) : second;
    return y + '-' + m + '-' + d + ' ' + h + ':' + minute + ':' + second;
}
//   截取字符串
function reString(data) {
    // 截取商品中有用的部分
       for (var i = 0; i < data.result.length; i++) {
            if (data.result[i].payload.indexOf("banner") != -1) {
                data.result[i].payload = data.result[i].payload.slice(0, data.result[i].payload.indexOf('"goods"') - 1) + '}}'
            }
            data.result[i].payload = data.result[i].payload.replace(/[\\]/g, '/');
            if (data.result[i].payload.indexOf(',"goods"') == -1) {
                data.result[i].payload = data.result[i].payload.slice(0, data.result[i].payload.indexOf('}}')) + '},"goods":null}}'
            }
            data.result[i].payload = data.result[i].payload.slice(0, data.result[i].payload.lastIndexOf('"username":"') + 12) + data.result[i].payload.slice(data.result[i].payload.lastIndexOf('"username":"') + 12, data.result[i].payload.indexOf(
                '"},"goods')).replace('"', '') + data.result[i].payload.slice(data.result[i].payload.indexOf('"},"goods'))
        }
        return data
}

function getCookie(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i].trim();
        if (c.indexOf(name) == 0) return c.substring(name.length, c.length);
    }
    return "";
}
var merchant_id = getCookie('storeid');
$.ajax({
    type: 'GET',
    url: 'http://api.hn.pinquduo.cn/api_2_0_1/chat/hx_login',
    data: {
        merchant_id: merchant_id,
    },
    dataType: 'json',
    jsonp: 'jsoncallback',
    async: true,
    success: function(data) {
        data = JSON.parse(data)
        var options = {
            apiUrl: "http://a1.easemob.com",
            user: data.result.username,
            //        accessToken: '13543390771',
            pwd: data.result.password,
            appKey: "1165160929115391#pqd",
            success: function(data) {
                username = data.user.username;
                if(flag) {
                flag = false;
                //    拉取好友列表
                $.ajax({
                    type: 'GET',
                    url: 'http://api.hn.pinquduo.cn/api_2_0_1/chat/get_unread',
                    data: {
                        user_id: username,
                    },
                    dataType: 'json',
                    jsonp: 'jsoncallback',
                    async: true,
                    success: function (data) {
                        reString(data)
                        if (data.result) {
                            for (var i = 0; i < data.result.length; i++) {
                                data.result[i].payload = JSON.parse(data.result[i].payload);
                                exts = exts.concat(data.result[i].payload.ext)

                            }
                            store_avatar = data.result[0].payload.ext.recevierUser.avatar;
                            store_username = data.result[0].payload.ext.recevierUser.username;
                            var html = template('firendListItem', {
                                list: data.result
                            });
                            $('.firendListItems').html(html)
                        }
                    },
                    error: function (xhr, type) {
                        console.log('Ajax error!');
                    }
                })
            }

            },
            fail: function() {
                alert('您与服务器已断开连接，请刷新登陆！')
            }
        };
        conn.open(options);
        conn.listen({
            onOpened: function(message) { //连接成功回调
                // 如果isAutoLogin设置为false，那么必须手动设置上线，否则无法收消息
                // 手动上线指的是调用conn.setPresence(); 如果conn初始化时已将isAutoLogin设置为true
                // 则无需调用conn.setPresence();
            },
            onClosed: function(message) {
                conn.open(options);
            }, //连接关闭回调
            onTextMessage: function(message) {
				if(message.ext.goods){
                var goods={
                    original:message.ext.goods.original,
                    goods_share_url:message.ext.goods.goods_share_url,
                    goods_name:message.ext.goods.goods_name,
                    prom:message.ext.goods.prom,
                    prom_price:message.ext.goods.prom_price,
                }
            }else{
                var goods=null;
            }
                //            判断聊天窗口是否打开
                if ($('#draggable').css('display') == 'none') {
                    //声音提示
                    setTimeout(function(){
                        $('#toneBox').html('<audio autoplay="autoplay" id="tone"><source src="Public/images/audio/4.mp3" type="audio/mpeg" /></audio>');
                    msgCount++;
                    if (msgCount > 99) {
                        msgCount == 99;
                    }
                    $('#newsCount').find('#count').text(msgCount).css('display', 'block');
                    }, 5000)
                }


                //            收到文本消息后发给本地服务器保存
                //            判断现在正在聊天的对象
                if (user_id == message.ext.senderUser.userid) {
                    //                正在聊天
                    console.log('正在聊天');
                    if (message.ext.goods) {
						//商品链接
                        var newMsg = '<div class="cahtWindowMsg"><div class="msgContainer"><div class="msgLeft"><img src="' + message.ext.senderUser.avatar + '" alt=""></div><div class="msgRight"><p class="msgInfo"><span>' +
                        formatDateTime(new Date().valueOf()) + '</span></p><p class="msgText" style="position: relative;padding-bottom: 2px"><a href="' + message.ext.goods.goods_share_url +'" target="_blank" style="display: flex;flex-wrap:nowrap;text-decoration: none;color: #000;font-style: normal;box-sizing: border-box;"><i style="display:block;width: 35%;overflow: hidden;"><img src="' +
                        message.ext.goods.original +'" alt="" style="width: 100%"></i><i style="margin-left:10px;width:75%"><i style="display: block;font-style: normal;width:100%;height: 40px;white-space: normal; overflow: hidden;text-overflow: ellipsis;font-size: 14px;margin-bottom: 10px;">' +
                        message.ext.goods.goods_name + '</i><i style="display: block;font-style: normal;font-size: 12px"><span style="color: red">￥' + message.ext.goods.prom_price + '</span>&nbsp;&nbsp;<span style="color: red">' +
                        message.ext.goods.prom + '</span>人团</i></i><img src="images/chat.png" alt="" style="position: absolute;top:10px;left: -7px;width: 8px"> </a></p></div></div></div>'
                    } else {
                        //                    文字消息
                        var newMsg = '<div class="cahtWindowMsg"> <div class="msgContainer"><div class="msgLeft"><img src="' + message.ext.senderUser.avatar + '" alt=""> </div><div class="msgRight"><p class="msgInfo"><span>' + formatDateTime(new Date().valueOf()) + '</span></p><p class="msgText" style="position: relative">' + message.data + '<img class="qipao" src="Public/images/chat/chat.png" alt="" style="position: absolute;top:10px;left: -7px;width: 8px"></p></div></div></div>'
                    }
                    $('.msgBox').append(newMsg);
                    $(".scroll").scrollTop($('.msgBox').height());
                    $.ajax({
                        type: 'POST',
                        url: 'http://api.hn.pinquduo.cn/api_2_0_1/chat/set_chat',
                        data: {
                            msg_id: message.id,
                            timestamp: parseInt((new Date()).valueOf() / 1000),
                            direction: 'outgoing',
                            to: message.ext.recevierUser.userid,
                            from: 'user' + message.ext.senderUser.userid,
                            chat_type: 'chat',
                            payload: JSON.stringify({
                                "bodies": [ //消息bodies
                                    {
                                        "msg": message.data.replace(/\n/g, ""), //消息内容
                                        "type": "txt" //文本消息类型
                                    }
                                ],
                                "ext": { //自定义扩展属性
                                    "recevierUser": message.ext.recevierUser,
                                    "senderUser": message.ext.senderUser,
                                    "goods":goods,
                                }
                            }),
                            status: 1
                        },
                        dataType: 'json',
                        jsonp: 'jsoncallback',
                        async: true,
                        success: function() {},
                        error: function(xhr, type) {
                            console.log('Ajax error!');
                        }
                    })
                } else {
                    //                未打开聊天窗口
                    $.ajax({
                        type: 'POST',
                        url: 'http://api.hn.pinquduo.cn/api_2_0_1/chat/set_chat',
                        data: {
                            msg_id: message.id,
                            timestamp: parseInt((new Date()).valueOf() / 1000),
                            direction: 'outgoing',
                            to: message.ext.recevierUser.userid,
                            from: 'user' + message.ext.senderUser.userid,
                            chat_type: 'chat',
                            payload: JSON.stringify({
                                "bodies": [ //消息bodies
                                    {
                                        "msg": message.data.replace(/\n/g, ""), //消息内容
                                        "type": "txt" //文本消息类型
                                    }
                                ],
                                "ext": { //自定义扩展属性
                                    "recevierUser": message.ext.recevierUser,
                                    "senderUser": message.ext.senderUser,
                                    "goods":goods,
                                }
                            }),
                            status: 0
                        },
                        dataType: 'json',
                        jsonp: 'jsoncallback',
                        async: true,
                        success: function() {
                            console.log('文本消息保存成功');
                            setTimeout(function() {
                                $.ajax({
                                    type: 'GET',
                                    url: 'http://api.hn.pinquduo.cn/api_2_0_1/chat/get_unread',
                                    data: {
                                        user_id: username,
                                    },
                                    dataType: 'json',
                                    jsonp: 'jsoncallback',
                                    async: true,
                                    success: function(data) {
                                        reString(data);
                                        exts = [];
                                        console.log(typeof(data.result));
                                        if (data.result) {
                                            for (var i = 0; i < data.result.length; i++) {
                                                data.result[i].payload = JSON.parse(data.result[i].payload);
                                                exts = exts.concat(data.result[i].payload.ext);
                                                if (user_id) {
                                                    if (user_id.indexOf(data.result[i].payload.ext.senderUser.userid) != -1) {
                                                        data.result[i].is_active = 'active'
                                                    } else {
                                                        data.result[i].is_active = ''
                                                    }
                                                }
                                            }
                                            //                                        console.log(data);
                                            console.log(exts);
                                            var html = template('firendListItem', {
                                                list: data.result
                                            });
                                            $('.firendListItems').html(html)
                                        }
                                    },
                                    error: function(xhr, type) {
                                        console.log('Ajax error!');
                                    }
                                })
                            }, 4000)
                        },
                        error: function(xhr, type) {
                            console.log('Ajax error!');
                        }
                    })
                }
            }, //收到文本消息
            onEmojiMessage: function(message) {
                console.log(message);
            }, //收到表情消息
            onPictureMessage: function(message) { //收到图片消息
                console.log(message);
                //            判断聊天窗口是否打开
                if ($('#draggable').css('display') == 'none') {
                    //声音提示
                    setTimeout(function(){
                        $('#toneBox').html('<audio autoplay="autoplay" id="tone"><source src="Public/images/audio/4.mp3" type="audio/mpeg" /></audio>');
                        msgCount++;
                        if (msgCount > 99) {
                        msgCount == 99;
                    }
                    $('#newsCount').find('#count').text(msgCount).css('display', 'block');
                    }, 5000)
                }
                if (message.from.indexOf('user') == -1) {
                    message.from = 'user' + message.from;
                }
                console.log(message.from);
                if (message.width / message.height >= 9 / 16) {
                    message.width = '90px';
                    message.height = 'auto';
                } else {
                    message.width = 'auto';
                    message.height = '160px';
                }
                //            判断现在正在聊天的对象
                if (user_id == message.ext.senderUser.userid) {
                    //                正在聊天
                    console.log("图片正在聊天");
                    var newMsg = '<div class="cahtWindowMsg"><div class="msgContainer"><div class="msgLeft"> <img src="' + message.ext.senderUser.avatar + '" alt=""></div> <div class="msgRight"> <p class="msgInfo" > <span>' + formatDateTime(new Date().valueOf()) + '</span></p> <div style="margin-top: 10px;padding: 6px 6px 2px 6px;background-color:#EEEEEE;display: inline-block;border-radius: 4px;position: relative"> <img onclick="imgClick(this)" src="' + message.url + '" alt="" style="height:' + message.height + ';width:' + message.width + ';border-radius: 4px;"> <img class="qipao" src="Public/images/chat/chat.png" alt="" style="position: absolute;top:10px;left: -7px;width: 10px"> </div> </div> </div> </div>';
                    $('.msgBox').append(newMsg);
                    $(".scroll").scrollTop($('.msgBox').height());
                    $.ajax({
                        type: 'POST',
                        url: 'http://api.hn.pinquduo.cn/api_2_0_1/chat/set_chat',
                        data: {
                            msg_id: message.id,
                            timestamp: parseInt((new Date()).valueOf() / 1000),
                            direction: 'outgoing',
                            to: message.ext.recevierUser.userid,
                            from: 'user' + message.ext.senderUser.userid,
                            chat_type: 'chat',
                            payload: JSON.stringify({
                                "bodies": [ //消息bodies
                                    {
                                        "msg": {
                                            "url": message.url,
                                            "width": message.width,
                                            "height": message.height,
                                        }, //消息内容
                                        "type": "img" //文本消息类型
                                    }
                                ],
                                "ext": { //自定义扩展属性
                                    "recevierUser": message.ext.recevierUser,
                                    "senderUser": message.ext.senderUser,
                                }
                            }),
                            status: 1
                        },
                        dataType: 'json',
                        jsonp: 'jsoncallback',
                        async: true,
                        success: function(data) {},
                        error: function(xhr, type) {
                            console.log('Ajax error!');
                        }
                    })
                } else {
                    $.ajax({
                        type: 'POST',
                        url: 'http://api.hn.pinquduo.cn/api_2_0_1/chat/set_chat',
                        data: {
                            msg_id: message.id,
                            timestamp: parseInt((new Date()).valueOf() / 1000),
                            direction: 'outgoing',
                            to: message.ext.recevierUser.userid,
                            from: 'user' + message.ext.senderUser.userid,
                            chat_type: 'chat',
                            payload: JSON.stringify({
                                "bodies": [ //消息bodies
                                    {
                                        "msg": {
                                            "url": message.url,
                                            "width": message.width,
                                            "height": message.height,

                                        }, //消息内容
                                        "type": "img" //文本消息类型
                                    }
                                ],
                                "ext": { //自定义扩展属性
                                    "recevierUser": message.ext.recevierUser,
                                    "senderUser": message.ext.senderUser,
                                }
                            }),
                            status: 0
                        },
                        dataType: 'json',
                        jsonp: 'jsoncallback',
                        async: true,
                        success: function() {
                            setTimeout(function() {
                                $.ajax({
                                    type: 'GET',
                                    url: 'http://api.hn.pinquduo.cn/api_2_0_1/chat/get_unread',
                                    data: {
                                        user_id: username,
                                    },
                                    dataType: 'json',
                                    jsonp: 'jsoncallback',
                                    async: true,
                                    success: function(data) {
                                        if (data.result) {
                                            for (var i = 0; i < data.result.length; i++) {
                                                data.result[i].payload = JSON.parse(data.result[i].payload);
                                                exts = exts.concat(data.result[i].payload.ext);
                                                if (user_id) {
                                                    if (user_id.indexOf(data.result[i].payload.ext.senderUser.userid) != -1) {
                                                        data.result[i].is_active = 'active'
                                                    } else {
                                                        data.result[i].is_active = ''
                                                    }
                                                }
                                            }
                                            console.log(data);
                                            var html = template('firendListItem', {
                                                list: data.result
                                            });
                                            $('.firendListItems').html(html)
                                        }
                                    },
                                    error: function(xhr, type) {
                                        console.log('Ajax error!');
                                    }
                                })
                            }, 4000)
                        },
                        error: function(xhr, type) {
                            console.log('Ajax error!');
                        }
                    })
                }
            }, //收到图片消息
            onCmdMessage: function(message) {}, //收到命令消息
            onAudioMessage: function(message) {}, //收到音频消息
            onLocationMessage: function(message) {}, //收到位置消息
            onFileMessage: function(message) {}, //收到文件消息
            onVideoMessage: function(message) {
                var node = document.getElementById('privateVideo');
                var option = {
                    url: message.url,
                    headers: {
                        'Accept': 'Public/chat2/audio/mp4'
                    },
                    onFileDownloadComplete: function(response) {
                        var objectURL = WebIM.utils.parseDownloadResponse.call(conn, response);
                        node.src = objectURL;
                    },
                    onFileDownloadError: function() {
                        console.log('File down load error.')
                    }
                };
                WebIM.utils.download.call(conn, option);
            }, //收到视频消息
            onPresence: function(message) {}, //收到联系人订阅请求、处理群组、聊天室被踢解散等消息
            onRoster: function(message) {}, //处理好友申请
            onInviteMessage: function(message) {}, //处理群组邀请
            onOnline: function() {}, //本机网络连接成功
            onOffline: function() {
                alert('网络环境异常，请检查网络状况！')
            }, //本机网络掉线
            onError: function(message) {}, //失败回调
            onBlacklistUpdate: function(list) { //黑名单变动
                // 查询黑名单，将好友拉黑，将好友从黑名单移除都会回调这个函数，list则是黑名单现有的所有好友信息
            }
        });
        //    打开聊天
        $('.firendListItems').on('click', 'li', function() {
            //改变好友列表样式
            $('.chatWindow').css('display', 'none');
            $(this).find('.status').css('display', 'none');
            //        好友列表信息
            ext = exts[$(this).attr('index')];
            //        console.log(ext);
            //        拉取聊天记录
            $('.firendListItems li').removeClass('active');
            $(this).addClass('active');
            $('.chatwindowTitle').html(this.children[1].children[0].innerText);
            var title = $('.chatwindowTitle').text();
            //        user_id = ext.senderUser.userid;
            user_id = ext.senderUser.userid;
            var obj = {
                page: 0,
                finished: true,
            };
            //        console.log(typeof (obj.msgArr));
            //        var finished =true;
            getChat(obj);
            $(".scroll").bind('scroll', function() {
                if (obj.finished && ($(this).scrollTop() < 200)) {
                    //                console.log(obj.page);
                    obj.finished = false;
                    getChat(obj);
                }
            });
        });
        //    获取聊天记录
        function getChat(obj) {

            $.ajax({
                type: 'GET',
                url: 'http://api.hn.pinquduo.cn/api_2_0_1/chat/get_chat',
                data: {
                    to: 'user' + ext.senderUser.userid,
                    from: ext.recevierUser.userid,
                    chat_type: 'chat',
                    page: obj.page,
                    pagesize: 20,
                },
                dataType: 'json',
                jsonp: 'jsoncallback',
                async: true,
                success: function(data) {
                    reString(data);
                    obj.page = obj.page + 1;
                    obj.finished = true;
                    //                console.log(obj);
                    if (!data.result) {
                        $(".scroll").unbind('scroll');
                        $('.msgBox').prepend('<p style="text-align: center;height: 40px;line-height:40px;color:#ccc;font-size: 14px">没有更多数据了</p>');
                        return false;
                    }
                    $('.chatWindow').css('display', 'block');
                    //                console.log(data);
                    for (var i = 0; i < data.result.length; i++) {
                        data.result[i].payload = JSON.parse(data.result[i].payload);
                        data.result[i].timestamp = formatDateTime(data.result[i].timestamp * 1000);
                        data.result[i].userid = user_id;
                    }

                    var html = template('chatWindowMsg', {
                        list: data.result
                    });
                    if (obj.page == 1) {
                        $('.msgBox').html(html);
                    } else {
                        $('.msgBox').prepend(html);
                    }

                    var style = '.' + ext.recevierUser.userid;
                    $(style).css({
                        'display': 'flex',
                        'justify-content': 'flex-end'
                    });
                    $(style).children('.msgContainer').css({
                        'display': 'flex',
                        'justify-content': 'flex-end'
                    });
                    $(style).children('.msgContainer').children('.msgRight').css({
                        'margin-right': '42px',
                        'margin-left': '0',
                        'display': 'flex',
                        'justify-content': 'flex-end',
                        'flex-wrap': 'wrap'
                    });
                    $(style).find('.msgText').css({
                        'background-color': '#D9EFFF'
                    });
                    $(style).find('.qipao').attr('src', 'Public/images/chat/chat2.png');
                    $(style).find('.qipao').css({
                        'right': '-7px',
                        'left': 'auto'
                    });
                    $(style).find('.msgLeft').css({
                        'right': '4px',
                        'left': 'auto'
                    });
                    $(style).find('.msgRight').css('text-align', 'end');
                    $(style).find('.msgRight div').css('background-color', '#D9EFFF');
                    if (obj.page == 1) {
                        $(".scroll").scrollTop($('.msgBox').height());
                    }
                },
                error: function(xhr, type) {
                    console.log('Ajax error!');
                }
            })
        }
        //    发送消息
        $('button').click(function() {
            var msgText = $('textarea').val();
            $('textarea').val('');
            if (msgText && !/^\s+$/.test(msgText)) {
                console.log(msgText);
                //            var newMsg='<div class="cahtWindowMsg" style="display: flex;justify-content:flex-end;"><div class="msgContainer" style="display:flex;justify-content:flex-end;"><div class="msgLeft" style="right: -35px; top: 2px;"><img src="'+store_avatar+'" alt=""></div><div class="msgRight"><p class="msgInfo"><span>'+store_username+'</span><span>'+formatDateTime(new Date().valueOf())+'</span></p><p class="msgText">'+msgText+'</p></div></div></div>'
                var newMsg = '<div class="cahtWindowMsg" style="display: flex;justify-content:flex-end;"> <div class="msgContainer" style="display:flex;justify-content:flex-end;"><div class="msgLeft" style="right: 4px;left: auto"><img src="' + store_avatar + '" alt=""> </div><div class="msgRight" style="margin-right: 42px; margin-left: 0px; display: flex; justify-content: flex-end; flex-wrap: wrap; text-align: end;"><p class="msgInfo"><span>' + formatDateTime(new Date().valueOf()) + '</span></p><p class="msgText" style="position: relative;background-color:#D9EFFF;">' + msgText + '<img class="qipao" src="Public/images/chat/chat2.png" alt="" style="position: absolute;top:10px;right: -7px;width: 8px"></p></div></div></div>';
                $('.msgBox').append(newMsg);
                $(".scroll").scrollTop($('.msgBox').height());
                var sendPrivateText = function() {
                    var id = conn.getUniqueId(); // 生成本地消息id
                    var msg = new WebIM.message('txt', id); // 创建文本消息
                    msg.set({
                        ext: {
                            "recevierUser": ext.senderUser,
                            "senderUser": ext.recevierUser,
                        },
                        msg: msgText, // 消息内容
                        to: ext.senderUser.userid, // 接收消息对象（用户id）
                        from: ext.recevierUser.userid,
                        roomType: false,
                        success: function(id, serverMsgId) {
                            //                        保存发送消息
                            $.ajax({
                                type: 'POST',
                                url: 'http://api.hn.pinquduo.cn/api_2_0_1/chat/set_chat',
                                data: {
                                    msg_id: serverMsgId,
                                    timestamp: parseInt((new Date()).valueOf() / 1000),
                                    direction: 'outgoing',
                                    to: 'user' + ext.senderUser.userid,
                                    from: ext.recevierUser.userid,
                                    chat_type: 'chat',
                                    payload: JSON.stringify({
                                        "bodies": [ //消息bodies
                                            {
                                                "msg": msgText.replace(/\n/g, ""), //消息内容
                                                "type": "txt" //文本消息类型
                                            }
                                        ],
                                        "ext": { //自定义扩展属性
                                            "recevierUser": ext.senderUser,
                                            "senderUser": ext.recevierUser,
                                        }
                                    }),
                                    status: 1
                                },
                                dataType: 'json',
                                jsonp: 'jsoncallback',
                                async: true,
                                success: function(data) {
                                    console.log(data);
                                },
                                error: function(xhr, type) {
                                    console.log('Ajax error!');
                                }
                            })
                        }
                    });
                    msg.body.chatType = 'singleChat';
                    conn.send(msg.body);
                };
                sendPrivateText();
            }
        });
        //    回车发送消息
        document.onkeyup = function(event) {
            var e = event || window.event;
            var keyCode = e.keyCode || e.which;
            if (keyCode == 13) {
                $('button').click();
            }
            //        switch (keyCode) {
            //            case 80:
            //                $("#btnPrint").click();
            //                break;
            //            case 83:
            //                $("#btnUpload").click();
            //                break;
            //            default:
            //                break;
            //        }
        }
        //    发送图片
        $('#myImg').on('click', function() {
            $('#hidden').click();
        });
        upImages = function(e) {
            console.log('img1');
            var to = parseInt(user_id.split('user')[1]);
            //        var blob = e.files[0].getAsFile();
            //        var url = window.URL.createObjectURL(blob);
            var blob = e.files[0];
            var url = URL.createObjectURL(e.files[0]);
            var id = conn.getUniqueId(); // 生成本地消息id
            var msg = new WebIM.message('img', id); // 创建图片消息
            msg.set({
                "ext": { //自定义扩展属性
                    "recevierUser": ext.senderUser,
                    "senderUser": ext.recevierUser,
                },
                apiUrl: WebIM.config.apiURL,
                file: {
                    data: blob,
                    url: url
                },
                to: ext.senderUser.userid, // 接收消息对象（用户id）
                from: ext.recevierUser.userid,
                roomType: false,
                chatType: 'singleChat',
                onFileUploadError: function(error) {
                    console.log('Error');
                },
                onFileUploadComplete: function(data) {
                    console.log(data);
                },
                success: function(id) {
                    $('#uploadImg').html('<div style="width: 100%;height:100%;background-color:#fff;position: absolute;"></div><img style="width:0;height:0;" id="image" src="' + url + '">');
                    var width = 0;
                    var height = 0;
                    document.querySelector('#image').onload = function() {
                        width = document.querySelector('#image').width;
                        height = document.querySelector('#image').height;
                        if (width / height >= 9 / 16) {
                            width = '90px';
                            height = 'auto';
                        } else {
                            width = 'auto';
                            height = '160px';
                        }
                        console.log(height);
                        console.log(width);
                        //                    var newMsg='<div class="cahtWindowMsg"><div class="msgContainer" style="float: right;margin-right: 40px"><div class="msgLeft" style="right: -35px; top: 2px;"><img src="'+store_avatar+'" alt=""></div><div class="msgRight"><p class="msgInfo"><span>'+store_username+'</span><span>'+formatDateTime(new Date().valueOf())+'</span></p><div style="margin-top: 20px"><img onclick="imgClick(this)" src="'+url+'" alt="" style="height:'+height+';width:'+width+';border-radius: 4px"></div></div></div></div>'
                        var newMsg = '<div class="cahtWindowMsg" style="display: flex; justify-content: flex-end;"><div class="msgContainer" style="display: flex; justify-content: flex-end;"><div class="msgLeft" style="right: 4px; left: auto;"> <img src="' + store_avatar + '" alt=""></div> <div class="msgRight" style="margin-right: 42px; margin-left: 0px; display: flex; justify-content: flex-end; flex-wrap: wrap; text-align: end;"> <p class="msgInfo" > <span>' + formatDateTime(new Date().valueOf()) + '</span></p> <div style="margin-top: 10px; padding: 6px 6px 2px; background-color: rgb(217, 239, 255); display: inline-block; border-radius: 4px; position: relative;"> <img onclick="imgClick(this)" src="' + url + '" alt="" style="height:' + height + ';width:' + width + ';border-radius: 4px"> <img class="qipao" src="Public/images/chat/chat2.png" alt="" style="position: absolute; top: 10px; left: auto; width: 10px; right: -7px;"> </div> </div> </div> </div>';
                        $('.msgBox').append(newMsg);
                        $(".scroll").scrollTop($('.msgBox').height());
                        //                图片转base64
                        var reader = new FileReader();
                        reader.readAsDataURL(e.files[0]);
                        reader.onload = function() {
                            url = this.result;
                            $.ajax({
                                type: 'POST',
                                url: 'http://api.hn.pinquduo.cn/api_2_0_1/chat/set_chat',
                                data: {
                                    msg_id: id,
                                    timestamp: parseInt((new Date()).valueOf() / 1000),
                                    direction: 'outgoing',
                                    to: 'user' + ext.senderUser.userid,
                                    from: ext.recevierUser.userid,
                                    chat_type: 'chat',
                                    payload: JSON.stringify({
                                        "bodies": [ //消息bodies
                                            {
                                                "msg": {
                                                    "url": url,
                                                    "width": width,
                                                    "height": height,

                                                }, //消息内容
                                                "type": "img" //文本消息类型
                                            }
                                        ],
                                        "ext": { //自定义扩展属性
                                            "recevierUser": ext.senderUser,
                                            "senderUser": ext.recevierUser,
                                        }
                                    }),
                                    status: 1
                                },
                                dataType: 'json',
                                jsonp: 'jsoncallback',
                                async: true,
                                success: function(data) {},
                                error: function(xhr, type) {
                                    console.log('Ajax error!');
                                }
                            })
                        }
                    }
                }
            });
            conn.send(msg.body);
        };


        $('.imgShadow').on('click', function() {
            $(this).css('display', 'none')
        });
        //    窗口拖拽
        $(function() {
            $("#draggable")
                .draggable({
                    handle: ".chatHeader",
                    containment: "parent"
                })
                .resizable({
                    maxHeight: 2048,
                    maxWidth: 2048,
                    minHeight: 400,
                    minWidth: 680
                })
        });
        //隐藏聊天窗口
        $('#hiddenBtn').on('click', function() {
            $('#draggable').css('display', 'none');
            //        消息数量归零
            msgCount = 0;
        });
        //    打开聊天窗口
        $('#newsCount').on('click', function() {
            $('#draggable').css('display', 'block');
            $('#newsCount').find('i').css('display', 'none');
        })
        //    发送图片悬停事件
        $('#myImg').hover(function() {
            $('#myImg').attr('src', 'Public/images/chat/photograph2@2x.png')
        }, function() {
            $('#myImg').attr('src', 'Public/images/chat/photograph@2x.png')
        })

    },
    error: function(xhr, type) {
        console.log('Ajax error!');
    }
})
