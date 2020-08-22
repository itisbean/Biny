<?php include App::$view_root . "/base/common.tpl.php" ?>
<?php include App::$view_root . "/base/header.tpl.php" ?>
<link href="<?= $webRoot ?>/static/css/demo.css" rel="stylesheet" type="text/css" />

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.6.0/dist/leaflet.css" integrity="sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ==" crossorigin="" />

<!-- Docs master nav -->
<header class="navbar navbar-static-top navbar-inverse" id="top" role="banner">
    <div class="container">
        <a href="<?= $webRoot ?>/demo/" class="navbar-brand">Map</a>
    </div>
</header>

<script src="https://unpkg.com/leaflet@1.6.0/dist/leaflet.js" integrity="sha512-gZwIG9x3wUXg2hdXF6+rVkLF/0Vi9U8D2Ntg4Ga5I5BZpVkVxlJWbSQtXPSiUTtC0TjtGOmxa1AJPuV0CPthew==" crossorigin=""></script>
<style>
    #mapid {
        height: 800px;
    }

    #tools {
        height: 50px;
        margin-left: 50px;
    }

    .tools-item,
    .tools-clear {
        width: 100px;
        display: inline-block;
        margin-right: 10px;
        text-align: center;
        border: 1px solid #ccc;
        cursor: pointer;
    }

    .activity {
        background-color: yellow;
    }

    .disabled {
        background-color: #ccc;
    }

    #floors {
        height: 50px;
        margin-left: 50px;
    }

    .floor-item {
        width: 50px;
        display: inline-block;
        margin-right: 10px;
        text-align: center;
        border: 1px solid #ccc;
        cursor: pointer;
    }

    .floor-activity {
        background-color: blue;
        color: #fff;
    }

    #category {
        height: 50px;
        margin-left: 50px;
    }

    .cate {
        width: 100px;
        margin-right: 10px;
        text-align: center;
        border: 1px solid #ccc;
        cursor: pointer;
        display: inline-block;
        background-color: #fff;
    }

    .cate-activity {
        display: inline-block;
        color: #fff;
    }

    .btn {
        padding: 1px 3px;
        margin-right: 5px;
    }
</style>

<div id="floors">
    <?php for ($i = $underFloors; $i > 0; $i--) : ?>
        <span class="floor-item" data-floor="<?php echo (0 - $i) ?>">B<?php echo $i; ?></span>
    <?php endfor; ?>
    <?php for ($i = 1; $i <= $groundFloors; $i++) : ?>
        <span class="floor-item" data-floor="<?php echo $i ?>">L<?php echo $i; ?></span>
    <?php endfor; ?>
</div>

<div id="tools">
    <span class="tools-item" data-id="0" id="draw-floor">绘制楼层</span>

    <span class="tools-item" data-id="1">点位</span>
    <!-- <span class="tools-item" data-id="5" id="point">线</span> -->
    <!-- <span class="tools-item" data-id="4">圆形区域</span> -->
    <span class="tools-item" data-id="6">矩形</span>
    <span class="tools-item" data-id="2">多边形</span>
    <span class="tools-item" data-id="8">停车位</span>
    <span class="tools-clear">清除选项</span>
</div>

<div id="category">
    <span class="cate block-cate" data-id="101" data-color="52c41a">普通区域</span>
    <span class="cate block-cate" data-id="102" data-color="fa541c">障碍物</span>
    <span class="cate block-cate" data-id="103" data-color="87e8de">公共区域</span>


    <span class="cate point-cate" data-id="201" data-color="ed4014">普通点位</span>
    <span class="cate point-cate" data-id="202" data-color="f90">直梯点位</span>
    <span class="cate point-cate" data-id="203" data-color="2d8cf0">扶梯点位</span>
</div>

<!-- accessToken: 'pk.eyJ1IjoiZG9ueWQiLCJhIjoiY2tlM3puNHhsMGF2czJ5cXZlNTd0a250dCJ9.vouBpOvOCHHM49VKRiPCOA' -->
<div id="mapid">

</div>

<?php include App::$view_root . "/base/footer.tpl.php" ?>
<script type="text/javascript" src="<?= $webRoot ?>/static/js/demo.js"></script>

<script>
    var buid = '<?php echo $buid;?>';
    var fid = '';

    var map;

    var layers = [];

    newMap();

    function newMap() {
        if (typeof map != 'undefined') {
            map.remove()
        }
        map = L.map('mapid', {
            center: [51.505, -0.09],
            zoom: 13,
            dragging: false,
            zoomControl: false,
            boxZoom: false,
            scrollWheelZoom: false,
            doubleClickZoom: false
        });
    }

    var selectValue = null;
    //点绘画
    function DrawPoint() {
        map.on('click', onClick);

        function onClick(e) {
            var lineColor = '#' + $('.cate-activity').eq(0).data('color');

            var k = layers.length;
            layers[k] = L.circle(e.latlng, {
                radius: 80,
                color: lineColor,
                fillOpacity: 0.8
            }).addTo(map);

            var name = '点位' + (k + 1);

            var data = {}
            data.outline = [e.latlng.lat, e.latlng.lng];
            data.name = name;
            var id = savePoint(data);

            layers[k].bindPopup('<b>Name: </b><input type="text" value="' + name + '" data-id="' + id + '" class="form-control" name="name" autofocus><br /><button onclick="updateName(\'' + id + '\', ' + k + ', 1)" class="btn btn-primary">保存</button><button onclick="deleteArea(\'' + id + '\', ' + k + ')" class="btn btn-default">删除</button>', {
                minWidth: 120
            }).bindTooltip(name, {
                keepInView: true
            });
        }
    }
    //圆绘画
    function DrawCircle() {
        var r = 0
        var i = null
        var tempCircle = new L.circle()
        // map.dragging.disable(); //将mousemove事件移动地图禁用
        map.on('mousedown', onmouseDown);
        map.on('mouseup', onmouseUp);
        map.on('mousemove', onMove)

        function onmouseDown(e) {
            i = e.latlng
            //确定圆心
        }

        function onMove(e) {
            if (i) {
                r = L.latLng(e.latlng).distanceTo(i)
                tempCircle.setLatLng(i)
                tempCircle.setRadius(r)
                tempCircle.setStyle({
                    color: '#ff0000',
                    fillColor: '#ff0000',
                    fillOpacity: 1
                })
                map.addLayer(tempCircle)

            }
        }

        function onmouseUp(e) {
            r = L.latLng(e.latlng).distanceTo(i) //计算半径
            L.circle(i, {
                radius: r,
                color: '#ff0000',
                fillColor: '#ff0000',
                fillOpacity: 1
            }).addTo(map)
            i = null
            r = 0
            // map.dragging.enable();
        }
    }
    //线绘画
    function DrawLine() {
        var points = []
        var lines = new L.polyline(points)
        var tempLines = new L.polyline([])
        map.on('click', onClick); //点击地图
        map.on('dblclick', onDoubleClick);

        function onClick(e) {

            points.push([e.latlng.lat, e.latlng.lng])
            lines.addLatLng(e.latlng)
            map.addLayer(lines)
            map.addLayer(L.circle(e.latlng, {
                color: '#00ff00',
                fillColor: '00ff00',
                fillOpacity: 1
            }))
            map.on('mousemove', onMove) //双击地图

        }

        function onMove(e) {
            if (points.length > 0) {
                ls = [points[points.length - 1],
                    [e.latlng.lat, e.latlng.lng]
                ]
                tempLines.setLatLngs(ls)
                map.addLayer(tempLines)
            }
        }

        function onDoubleClick(e) {
            L.polyline(points).addTo(map)
            points = []
            lines = new L.polyline(points)
            map.off('mousemove')
        }
    }
    //多边形绘画
    function DrawPolygon(type) {
        if (type == 0 && fid != '') {
            return
        }

        // 正常区域
        var lineColor = '';

        var points = [];
        var latlngs = [];

        let lines;
        let tempLines;

        map.on('click', onClick); // 单击画点
        map.on('dblclick', onDoubleClick); // 双击结束
        map.on('mousemove', onMove); // 鼠标移动
        map.on('contextmenu', onRemove); // 右击取消

        function onClick(e) {
            if (lineColor == '') {
                if (type == 0) {
                    lineColor = '#ccc';
                } else {
                    lineColor = '#' + $('.cate-activity').eq(0).data('color');
                }

                lines = new L.polyline([], {
                    color: lineColor
                });
                tempLines = new L.polyline(e.latlng, {
                    dashArray: 5,
                    color: lineColor
                })
            }

            latlngs.push([e.latlng.lat, e.latlng.lng])
            points.push([e.containerPoint.x, e.containerPoint.y])

            lines.addLatLng(e.latlng)
            map.addLayer(tempLines)
            map.addLayer(lines)
        }

        function onMove(e) {
            if (latlngs.length > 0) {
                ls = [latlngs[latlngs.length - 1],
                    [e.latlng.lat, e.latlng.lng]
                ]
                tempLines.setLatLngs(ls)
                map.addLayer(tempLines)
            }
        }

        function onDoubleClick(e) {
            tempLines.remove();
            lines.remove();

            var outline = [];
            for (let k in points) {
                outline.push(points[k][0])
                outline.push(points[k][1])
            }

            var data = {}
            data.outline = outline
            data.latlngs = latlngs

            if (type == 0) {
                saveFloor(fid, data);
                L.polygon([latlngs], {
                    color: lineColor,
                    weight: 1,
                }).addTo(map);

            } else {
                var k = layers.length;
                layers[k] = new L.polygon([latlngs], {
                    color: lineColor,
                    fillOpacity: 0.2,
                    weight: 1,
                }).addTo(map);

                var name = '区域' + (k + 1);
                var id = uuid();

                data.id = id;
                data.name = name;
                
                layers[k].bindPopup('<b>Name: </b><input type="text" value="' + name + '" data-id="' + id + '" class="form-control" name="name" autofocus><br /><button onclick="updateName(\'' + id + '\', ' + k + ', 1)" class="btn btn-primary">保存</button><button onclick="deleteArea(\'' + id + '\')" class="btn btn-default">删除</button>', {
                    minWidth: 120
                }).bindTooltip(name, {
                    keepInView: true
                });

                saveArea(data);
            }

            lineColor = '';
            latlngs = []
            points = []
        }

        function onRemove(e) {
            tempLines.remove();
            lines.remove();

            lineColor = '';
            latlngs = []
            points = []
        }
    }
    //矩形绘画
    function DrawOrthogon() {
        let rectangle
        let tmprec
        var latlngs = []

        var points = [];

        var lineColor = ''

        map.on('click', onClick); //点击地图
        map.on('dblclick', onDoubleClick);
        map.on('contextmenu', onRemove); // 右击取消

        //map.off(....) 关闭该事件
        function onClick(e) {

            if (typeof tmprec != 'undefined') {
                tmprec.remove()
            }
            //左上角坐标
            latlngs[0] = [e.latlng.lat, e.latlng.lng]
            points[0] = [e.containerPoint.x, e.containerPoint.y]

            //开始绘制，监听鼠标移动事件
            map.on('mousemove', onMove)
            map.off('click')
        }

        function onMove(e) {
            if (lineColor == '') {
                lineColor = '#' + $('.cate-activity').eq(0).data('color')
            }

            latlngs[1] = [e.latlng.lat, e.latlng.lng]
            //删除临时矩形
            if (typeof tmprect != 'undefined') {
                tmprect.remove()
            }
            //添加临时矩形
            tmprect = L.rectangle(latlngs, {
                dashArray: 5,
                color: lineColor
            }).addTo(map)
        }

        function onDoubleClick(e) {

            //矩形绘制完成，移除临时矩形，并停止监听鼠标移动事件
            tmprect.remove();
            map.off('mousemove');
            map.on('click', onClick);

            //右下角坐标
            latlngs[1] = [e.latlng.lat, e.latlng.lng]
            points[1] = [e.containerPoint.x, e.containerPoint.y]

            var k = layers.length;
            layers[k] = L.rectangle(latlngs, {
                color: lineColor,
                fillOpacity: 0.2,
                weight: 1
            }).addTo(map);

            lineColor = '';

            var name = '区域' + (k + 1);

            var data = {}
            var id = uuid();

            data.id = id;
            data.outline = [points[0][0], points[0][1], points[1][0], points[0][1], points[1][0], points[1][1], points[0][0], points[1][1], points[0][0], points[0][1]];
            data.latlngs = latlngs;
            data.name = name;
            
            layers[k].bindPopup('<b>Name: </b><input type="text" value="' + name + '" data-id="' + id + '" class="form-control" name="name" autofocus><br /><button onclick="updateName(\'' + id + '\', ' + k + ', 1)" class="btn btn-primary">保存</button><button onclick="deleteArea(\'' + id + '\', ' + k + ')" class="btn btn-default">删除</button>', {
                minWidth: 120
            }).bindTooltip(name, {
                keepInView: true
            });

            saveArea(data);
        }

        function onRemove(e) {
            tmprect.remove();
            map.off('mousemove');
            map.on('click', onClick);
            lineColor = '';
        }
    }

    //停车位
    function DrawParking() {
        let tempdot
        var latlngs = []

        var points = [];

        var lineColor = '#d3adf7';

        map.on('click', onClick); //点击地图
        map.on('dblclick', onDoubleClick);
        map.on('contextmenu', onRemove); // 右击取消

        function onClick(e) {
            //左上角坐标
            latlngs[0] = [e.latlng.lat, e.latlng.lng]
            points[0] = [e.containerPoint.x, e.containerPoint.y]

            console.log(points[0])

            if (typeof tempdot != 'undefined') {
                tempdot.remove()
            }
            tempdot = L.circle(e.latlng, {
                radius: 50,
                color: lineColor,
            }).addTo(map);

            //开始绘制，监听鼠标移动事件
            map.off('click')
        }

        var key = layers.length;

        function onDoubleClick(e) {
            tempdot.remove();

            //矩形绘制完成，移除临时矩形，并停止监听鼠标移动事件
            // tmprect.remove();
            map.off('mousemove');
            map.on('click', onClick);

            //右下角坐标
            latlngs[1] = [e.latlng.lat, e.latlng.lng]
            points[1] = [e.containerPoint.x, e.containerPoint.y]

            var ret = splitSquare(points[0], points[1], 2);

            if (!ret) {
                return
            }

            var pointsArr = ret[0];
            var latlngsArr = ret[1];

            for (var i in latlngsArr) {
                var k = key + parseInt(i)
                layers[k] = L.rectangle(latlngsArr[i], {
                    color: lineColor,
                    fillOpacity: 0.2,
                    weight: 1
                }).addTo(map);

                var name = '车位' + (k + 1);
                var id = uuid();
                var data = {};
                data.id = id;
                data.outline = pointsArr[i];
                data.latlngs = latlngsArr[i];
                data.name = name;
                data.category = 110;
                // debugger
                layers[k].bindPopup('<b>Name: </b><input type="text" value="' + name + '" data-id="' + id + '" class="form-control" name="name" autofocus><br /><button onclick="updateName(\'' + id + '\', ' + k + ', 1)" class="btn btn-primary">保存</button><button onclick="deleteArea(\'' + id + '\', ' + k + ')" class="btn btn-default">删除</button>', {
                    minWidth: 120
                }).bindTooltip(name, {
                    keepInView: true
                });

                saveArea(data);
            }
        }

        function onRemove(e) {
            tempdot.remove();
            map.on('click', onClick);
        }

        function splitSquare(start, end, type) {
            const w = 60
            const l = 100

            var wgap = 20;
            var lgap = 50;

            var width = Math.abs(end[0] - start[0]);
            var length = Math.abs(end[1] - start[1]);

            var wnum = Math.floor(width / (w+wgap));
            var lnum = Math.floor(length / (l+lgap));

            if (wnum < 1 || lnum < 1) {
                return null
            }

            // if (lnum > 2) {
            //     lnum = 2
            // }

            console.log('start', start)
            // console.log('num', wnum, lnum)

            
            // if (wnum > 1) {
            //     wgap = (width - wnum * w) / (wnum - 1);
            // }
            
            // if (lnum > 1) {
            //     lgap = (length - lnum * l) / (lnum - 1);
            // }

            var lts = [];
            var pts = [];
            for (let i = 0; i < wnum; i++) {
                var curx = start[0] + ((w + wgap) * i);
                for (let j = 0; j < lnum; j++) {
                    var cury = start[1] + ((l + lgap) * j);

                    console.log('i,j', curx, cury)
                    var pt = [curx, cury, curx + w, cury, curx + w, cury + l, curx, cury + l, curx, cury];

                    var ls = map.layerPointToLatLng([curx, cury])
                    var le = map.layerPointToLatLng([curx + w, cury + l])
                    var lt = [
                        [ls.lat, ls.lng],
                        [le.lat, le.lng]
                    ];
                    pts.push(pt)
                    lts.push(lt)
                }
            }

            if (type == 1) {
                tmprec = L.rectangle(lts, {
                    dashArray: 5,
                    color: lineColor
                }).addTo(map)
            } else {
                return [pts, lts];
            }
        }
    }


    //保存楼层
    function saveFloor(id, data) {
        data.floor = $(".floor-activity").eq(0).data('floor');
        $.ajax({
            type: "post",
            url: "/saveFloor",
            data: {
                'id': id,
                'buid': buid,
                'data': data
            },
            success: function(result) {
                fid = result.ret.id;
            }
        });
    }

    //保存区域
    function saveArea(data) {
        if (typeof data.id == 'undefined') {
            data.id = uuid()
        } 
        if (typeof data.category == 'undefined') {
            data.category = $('.cate-activity').eq(0).data('id');
        }
        
        $.ajax({
            type: "post",
            url: "/saveArea",
            data: {
                'fid': fid,
                'data': data
            },
            success: function(result) {
            }
        });
    }

    //保存点位
    function savePoint(data) {
        var id = '';
        data.category = $('.cate-activity').eq(0).data('id');
        $.ajax({
            type: "post",
            url: "/savePoint",
            async: false,
            data: {
                'fid': fid,
                'data': data
            },
            success: function(result) {
                id = result.ret.id;
            }
        });
        return id;
    }

    //修改名称
    function updateName(id, k, type) {
        var name = $('input[name="name"][data-id="' + id + '"]').val();
        var data = {}
        data.id = id;
        data.name = name;
        if (type == 1) {
            saveArea(data);
        } else {
            savePoint(data)
        }
        layers[k].closePopup();
        layers[k].setPopupContent('<b>Name: </b><input type="text" value="' + name + '" data-id="' + id + '" class="form-control" name="name" autofocus><br /><button onclick="updateName(\'' + id + '\', ' + k + ', 1)" class="btn btn-primary">保存</button><button onclick="deleteArea(\'' + id + '\', ' + k + ')" class="btn btn-default">删除</button>');
        layers[k].setTooltipContent(name);
    }

    //删除区域
    function deleteArea(id, k) {
        $.ajax({
            type: "post",
            url: "/deleteArea",
            data: {
                'id': id,
            },
            success: function(result) {
                layers[k].remove();
            }
        });

    }

    //删除点位
    function deletePoint(id, k) {
        $.ajax({
            type: "post",
            url: "/deletePoint",
            data: {
                'id': id,
            },
            success: function(result) {
                layers[k].remove();
            }
        });
    }

    function uuid() {

        var s = [];

        var hexDigits = "0123456789abcdef";

        for (var i = 0; i < 36; i++) {

            s[i] = hexDigits.substr(Math.floor(Math.random() * 0x10), 1);

        }

        s[14] = "4"; // bits 12-15 of the time_hi_and_version field to 0010

        s[19] = hexDigits.substr((s[19] & 0x3) | 0x8, 1); // bits 6-7 of the clock_seq_hi_and_reserved to 01

        s[8] = s[13] = s[18] = s[23] = "-";



        var uuid = s.join("");

        return uuid;

    }
</script>

<script>
    //点击图形工具
    $(".tools-item").on('click', function() {
        $(".tools-item").removeClass("activity");
        if ($(this).hasClass("disabled")) {
            return
        }
        $(this).addClass("activity");

        var id = $(this).data("id");

        if (id != '0' && fid == '') {
            return
        }

        $(".cate").hide();

        if (id == "0") { //楼层
            map.off();
            DrawPolygon(0);
        } else if (id == "1") { //点
            map.off();
            DrawPoint();
            $(".point-cate").show();
            $(".point-cate").eq(0).click();
        } else if (id == "2") { //多边形
            map.off();
            DrawPolygon(1);
            $(".block-cate").show();
            $(".block-cate").eq(0).click();
        } else if (id == "3") {
            map.off();
            DrawPolygon(2);
        } else if (id == "4") {
            map.off();
            DrawCircle();
        } else if (id == "5") {
            map.off();
            DrawLine();
        } else if (id == "6") { //矩形
            map.off();
            DrawOrthogon();
            $(".block-cate").show();
            $(".block-cate").eq(0).click();
        } else if (id == "8") {
            map.off();
            DrawParking();
        }
    });

    //清空图形工具选择
    $(".tools-clear").on('click', function() {
        $(".tools-item").removeClass("activity");
        $(".cate").hide();
        map.off(); // 关闭该事件
    });

    //进入页面初始化
    $(function() {
        $(".floor-item").eq(0).click();
    });

    //点击楼层，画出该楼层图
    $(".floor-item").on('click', function() {
        fid = '';
        $(".cate").hide();
        $(".tools-clear").click();
        newMap();

        $(".floor-item").removeClass("floor-activity");
        $(this).addClass("floor-activity");

        var floor = $(this).data('floor');
        $.ajax({
            type: "get",
            url: "/getFloor",
            data: {
                'buid': buid,
                'floor': floor
            },
            success: function(result) {
                var floorData = result.ret.data;
                if (floorData.id) {
                    fid = floorData.id;
                    if (floorData.temp_latlngs) {
                        L.polygon(floorData.temp_latlngs, {
                            color: '#ccc'
                        }).addTo(map)
                    }
                    $("#draw-floor").addClass("disabled");

                    var areas = floorData.areas;

                    layers = [];

                    for (let k in areas) {
                        if (areas[k].temp_latlngs.length == 1) { //画点
                            var color = $('.point-cate[data-id="' + areas[k].category + '"]').data('color');

                            layers[k] = new L.circle(areas[k].temp_latlngs[0], {
                                color: '#' + color,
                                radius: 80,
                                fillOpacity: 0.8
                            }).addTo(map);

                            layers[k].bindPopup('<b>Name: </b><input type="text" value="' + areas[k].name + '" data-id="' + areas[k].id + '" class="form-control" name="name" autofocus><br /><button onclick="updateName(\'' + areas[k].id + '\', ' + k + ', 2)" class="btn btn-primary">保存</button><button onclick="deletePoint(\'' + areas[k].id + '\', ' + k + ')" class="btn btn-default">删除</button>', {
                                minWidth: 120
                            }).bindTooltip(areas[k].name, {
                                keepInView: true
                            });

                        } else if (areas[k].temp_latlngs.length == 2) { //画矩形
                            var color = '';
                            if (areas[k].category == '110') {
                                color = 'd3adf7';
                            } else {
                                color = $('.block-cate[data-id="' + areas[k].category + '"]').data('color');
                            }

                            layers[k] = new L.rectangle(areas[k].temp_latlngs, {
                                color: '#' + color,
                                fillOpacity: 0.2,
                                weight: 1
                            }).addTo(map);

                            layers[k].bindPopup('<b>Name: </b><input type="text" value="' + areas[k].name + '" data-id="' + areas[k].id + '" class="form-control" name="name" autofocus><br /><button onclick="updateName(\'' + areas[k].id + '\', ' + k + ', 1)" class="btn btn-primary">保存</button><button onclick="deleteArea(\'' + areas[k].id + '\', ' + k + ')" class="btn btn-default">删除</button>', {
                                minWidth: 120
                            }).bindTooltip(areas[k].name, {
                                keepInView: true
                            });

                        } else { // 画多边形
                            var color = $('.block-cate[data-id="' + areas[k].category + '"]').data('color');

                            layers[k] = new L.polygon(areas[k].temp_latlngs, {
                                color: '#' + color,
                                fillOpacity: 0.2,
                                weight: 1
                            }).addTo(map);
                            layers[k].bindPopup('<b>Name: </b><input type="text" value="' + areas[k].name + '" data-id="' + areas[k].id + '" class="form-control" name="name" autofocus><br /><button onclick="updateName(\'' + areas[k].id + '\', ' + k + ', 1)" class="btn btn-primary">保存</button><button onclick="deleteArea(\'' + areas[k].id + '\', ' + k + ')" class="btn btn-default">删除</button>', {
                                minWidth: 120
                            }).bindTooltip(areas[k].name, {
                                keepInView: true
                            });

                        }
                    }

                } else { // 楼层图不存在
                    $("#draw-floor").removeClass("disabled");
                    $("#draw-floor").click();
                }
            }
        });
    });

    //点击点位类别
    $(".point-cate").on('click', function() {
        $(".point-cate").removeClass('cate-activity');
        $(this).addClass('cate-activity');
        var color = $(this).data('color');
        $(".point-cate").css({
            'background-color': '#fff'
        });
        $(this).css({
            'background-color': '#' + color,
        });
    })
    //点击区域类别
    $(".block-cate").on('click', function() {
        $(".block-cate").removeClass('cate-activity');
        $(this).addClass('cate-activity');
        var color = $(this).data('color');
        $(".block-cate").css({
            'background-color': '#fff'
        });
        $(this).css({
            'background-color': '#' + color,
        });
    })
</script>