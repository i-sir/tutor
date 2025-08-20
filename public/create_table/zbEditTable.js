const zbEditTable = function () {
    "use strict";

    //点击input的回调, this是被点击的input
    this.onClickInput = function () {
        //console.log(this);
    }

    //添加一个可编辑行的回调
    this.onAddRowInput = function (table_id) {
        let input_values = ['', 'varchar(225)', '', 'YES', 'NULL', '', ''];//设置默认值

        let tbody = document.getElementById(table_id).getElementsByTagName('tbody')[0];
        let newTr = tbody.lastElementChild.cloneNode(true);
        let inputs = newTr.getElementsByTagName('input');
        for (let i = 0; i < inputs.length; i++) {
            inputs[i].value = input_values[i];
            inputs[i].addEventListener('click', this.onClickInput.bind(inputs[i]));//追加事件
        }
        tbody.appendChild(newTr);

        this.reCodeInput(table_id);
    }

    //删除一个可编辑行的回调
    this.onDelRowInput = function (btn) {
        let tr = btn.parentNode.parentNode;
        let tbody = tr.parentNode;
        if (tbody.children.length == 1) {
            alert('不能删除所有行');
            return false;
        } else {
            tbody.removeChild(tr);
        }

    }

    //重新对input坐标编码
    this.reCodeInput = function (table_id) {
        console.log('更新inputs坐标编码: ' + table_id);
        let tbody = document.getElementById(table_id).getElementsByTagName('tbody')[0];
        let trs = tbody.getElementsByTagName('tr');
        for (let i = 0; i < trs.length; i++) {
            let inputs = trs[i].getElementsByTagName('input');
            for (let j = 0; j < inputs.length; j++) {
                inputs[j].setAttribute('coordinate', i + ',' + j);
            }
        }
    }

    //根据传入的数据,创建一个可编辑的表格
    this.createEditTable = function (config = {}) {

        let table = document.createElement('table');

        if (config['id'] !== undefined) {
            table.setAttribute('id', config['id']);
        }

        let tr = document.createElement('tr');

        //表头
        let thead = document.createElement('thead');
        if (config['thead'] && config['thead']['values']) {
            let trCopy = tr.cloneNode();
            let title = config['thead']['values'];
            for (let i = 0; i < title.length; i++) {
                trCopy.appendChild(this.createReadCell({text: title[i]}));
            }
            thead.appendChild(trCopy);
        }
        table.appendChild(thead);

        //表内容
        let tbody = document.createElement('tbody');

        if (config['tbody'] && config['tbody']['values']) {
            let arr = config['tbody']['values'];
            for (let y = 0; y < arr.length; y++) {
                let trCopy = tr.cloneNode();
                for (let x in arr[y]) {
                    let attrs = {attrs: {'coordinate': y + ',' + x, value: arr[y][x], table_id: config['id']}};
                    trCopy.appendChild(this.createEditCell(attrs));
                }

                tbody.appendChild(trCopy);
            }
        }
        table.appendChild(tbody);

        return table; //返回DOM元素, target.appendChild(table);
    }

    //根据传入的数据,创建一个只读的表格
    this.createReadTable = function (config = {}) {

        let table = document.createElement('table');

        if (config['id'] !== undefined) {
            table.setAttribute('id', config['id']);
        }

        if (config['table_class'] !== undefined) {
            table.classList.add(config['table_class']);
        }

        let tr = document.createElement('tr');

        //表头
        let thead = document.createElement('thead');
        if (config['thead'] && config['thead']['values']) {
            let trCopy = tr.cloneNode();
            let title = config['thead']['values'];
            for (let i = 0; i < title.length; i++) {
                trCopy.appendChild(this.createReadCell({tag: 'th', text: title[i]}));
            }
            thead.appendChild(trCopy);
        }
        table.appendChild(thead);


        //表内容
        let tbody = document.createElement('tbody');
        if (config['tbody'] && config['tbody']['values']) {
            let arr = config['tbody']['values'];
            for (let y = 0; y < arr.length; y++) {
                let trCopy = tr.cloneNode();
                for (let x in arr[y]) {
                    trCopy.appendChild(this.createReadCell({
                        attrs: {'coordinate': y + ',' + x, table_id: config['id']},
                        text: arr[y][x]
                    }));
                }

                tbody.appendChild(trCopy);
            }
        }
        table.appendChild(tbody);

        return table; //返回DOM元素, target.appendChild(table);
    }

    //创建可以动态加减, 可以编辑的表格
    this.createResponseEditTable = function (config = {}) {

        let table = document.createElement('table');
        table.setAttribute('id', config['id']);

        let tr = document.createElement('tr');

        //表头
        let thead = document.createElement('thead');
        if (config['thead'] && config['thead']['values']) {
            let trCopy = tr.cloneNode();
            let title = config['thead']['values'];
            for (let i = 0; i < title.length; i++) {
                trCopy.appendChild(this.createReadCell({text: title[i]}));
            }
            trCopy.appendChild(this.createReadCell({text: ''}));
            thead.appendChild(trCopy);
        }
        table.appendChild(thead);

        //内容
        let tbody = document.createElement('tbody');
        if (config['tbody']) {
            let rowNum = 1; //默认行数
            let colNum = 1; //默认列数
            let values = []; //默认值

            if (config['tbody']['values'].length > 0) {
                values = config['tbody']['values'];
                rowNum = config['tbody']['values'].length;
                colNum = config['tbody']['values'][0].length;

            } else if (config['tbody']['default_row'] && config['tbody']['default_col']) {
                rowNum = config['tbody']['default_row']; //默认行数
                colNum = config['tbody']['default_col']; //默认列数
            }

            for (let y = 0; y < rowNum; y++) {
                let trCopy = tr.cloneNode();

                //每一行的其余td
                for (let x = 0; x < colNum; x++) {
                    let value = '';
                    if (values.length > 0 && values[y] && values[y][x]) {
                        value = values[y][x];
                    }

                    let attrs = {attrs: {coordinate: y + ',' + x, value: value, table_id: config['id']}};
                    trCopy.appendChild(this.createEditCell(attrs));
                }

                let td = document.createElement('td');
                let btn = document.createElement('button');
                btn.innerText = '-';
                btn.setAttribute('style', 'width:40px;height:40px;');
                btn.setAttribute('onclick', 'let tr = this.parentNode.parentNode; let tbody = tr.parentNode; if(tbody.children.length > 1){tbody.removeChild(tr);}');
                //btn.addEventListener('click', this.onDelRowInput.bind(this, btn)); //动态添加行时会比较麻烦, 此功能很简单, 无需通过回调处理
                td.appendChild(btn);
                trCopy.appendChild(td);

                tbody.appendChild(trCopy);
            }
        }

        table.appendChild(tbody);

        let tfoot = document.createElement('tfoot');
        let btn = document.createElement('button');
        btn.innerText = '+';
        btn.setAttribute('style', 'width:40px;height:40px;padding:0;text-align:center;')
        btn.addEventListener('click', this.onAddRowInput.bind(this, config['id']));
        tfoot.appendChild(btn);
        table.appendChild(tfoot);

        return table; //返回DOM元素, target.appendChild(table);
    }

    //创建可以编辑的表格td
    this.createEditCell = function (config = {}) {
        let td = document.createElement('td');

        let input = document.createElement('input');
        for (let attr in config['attrs']) {
            input.setAttribute(attr, config['attrs'][attr]);
        }

        if (config['attrs']['type'] == undefined) {
            input.setAttribute('type', 'text');
        }

        input.addEventListener('click', this.onClickInput.bind(input));

        td.appendChild(input);
        return td;
    }

    //创建只读的表格td
    this.createReadCell = function (config = {}) {
        let tag = (config['tag'] === undefined) ? 'td' : config['tag'];
        let td = document.createElement(tag);
        td.innerHTML = config['text'];
        if (config['attrs']) {
            for (let attr in config['attrs']) {
                td.setAttribute(attr, config['attrs'][attr]);
            }
        }

        return td;
    }

    //将字符串数据转为数组
    this.convertStrToArr = function (str, trGap = ';', tdGap = ',') {
        let arrTr = str.split(trGap); //拆分成多行

        let data = [];
        for (let i = 0; i < arrTr.length; i++) {
            let arrTd = arrTr[i].split(tdGap); //拆分成td

            data.push(arrTd);
        }

        return data;
    }

    //获取表格input的值, 返回二维数组
    this.getInputsArray = function (id) {
        let trs = document.getElementById(id).getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        let data = [];
        for (let i = 0; i < trs.length; i++) {
            let rowData = [];
            let inputs = trs[i].getElementsByTagName('input');
            for (let j = 0; j < inputs.length; j++) {
                rowData.push({name: inputs[j]['name'], 'value': inputs[j]['value']});
            }

            data.push(rowData);
        }
        return data;
    }


    this.getInputs = function (id, trGap = ';', tdGap = ',') {
        let trs = document.getElementById(id).getElementsByTagName('tr');
        let data = [];
        for (let i = 0; i < trs.length; i++) {
            let rowData = [];
            let inputs = trs[i].getElementsByTagName('input');
            for (let j = 0; j < inputs.length; j++) {
                rowData.push(inputs[j].value);
            }
            data.push(rowData.join(tdGap));
        }

        return data.join(trGap);
    }

    this.domToHtml = function (node) {
        let tmpNode = document.createElement("div");
        tmpNode.appendChild(node);
        return tmpNode.innerHTML;
        //tmpNode = node = null; // prevent memory leaks in IE
    }

    //获取表格的值
    // str: 通过getInputs获取的字符串值; row 行数(从0开始); col 列数(从0开始)
    this.getValue = function (str, row = 0, col = 0) {
        let arr = this.convertStrToArr(str);
        let line = arr[row];
        let key = row + '_' + col
        return line[key];
    }

    //更改input的属性值
    // id 表格的ID
    // arr: [{coordinate:'0,0', attrs:[name:'xxx', value:'yyyy']}]
    this.setInputAttributes = function (id, arr) {
        for (let i = 0; i < arr.length; i++) {
            let config = arr[i];
            let coordinate = config['coordinate'].split(',');
            let rowIdx = coordinate[0];
            let colIdx = coordinate[1];
            let attrs = config['attrs'];
            let table = document.getElementById(id);
            let tbody = table.getElementsByTagName('tbody')[0];
            let trs = tbody.getElementsByTagName('tr');


            //指定行,列坐标进行修改
            if (rowIdx.length > 0 && colIdx.length > 0) {
                let ri = parseInt(rowIdx);
                let ci = parseInt(colIdx)
                let tds = trs[ri].getElementsByTagName('td');
                let input = tds[ci].getElementsByTagName('input')[0];


                for (let attr in attrs) {
                    input.setAttribute(attr, attrs[attr]);
                }
            }
            //指定行, 但未指定列, 更改此行的所有列
            else if (rowIdx.length > 0 && colIdx.length == 0) {
                let tds = trs[rowIdx].getElementsByTagName('td');
                for (let j = 0; j < tds.length; j++) {
                    let input = tds[j].getElementsByTagName('input')[0];
                    for (let attr in attrs) {
                        input.setAttribute(attr, attrs[attr]);
                    }
                }
            }
            //指定列, 但未指定行, 更改所有行的此列
            else if (rowIdx.length == 0 && colIdx.length > 0) {
                for (let k = 0; k < trs.length; k++) {
                    let tds = trs[k].getElementsByTagName('td');
                    let input = tds[colIdx].getElementsByTagName('input')[0];
                    for (let attr in attrs) {
                        input.setAttribute(attr, attrs[attr]);
                    }
                }
            }
        }
    }

    //获取input的属性
    //id: 表格ID,
    //coordinate: 坐标(row,col)
    this.getInputByCoordinate = function (id, coordinate) {

        let arr = coordinate.split(',')
        let rowIdx = arr[0];
        let colIdx = arr[1];

        let table = document.getElementById(id);
        let tbody = table.getElementsByTagName('tbody')[0];
        let trs = tbody.getElementsByTagName('tr');

        if (rowIdx.length > 0 && colIdx.length > 0) {
            let inputs = trs[rowIdx].getElementsByTagName('input');
            return inputs[colIdx];

        } else if (rowIdx.length == 0 && colIdx.length > 0) {
            let inputs = [];
            for (let i = 0; i < trs.length; i++) {
                inputs.push(trs[i].getElementsByTagName('input')[colIdx])
            }

            return inputs;
        }

    }

    //从一个对象中批量挑选出需要的字段
    this.pick_obj_data = function (objOld, objFilter) {
        let objNew = {};
        for (let k in objOld) {
            if (objFilter[k] !== undefined) {
                objNew[k] = objOld[k];
            }
        }

        for (let j in objFilter) {
            if (objNew[j] === undefined) {
                objNew[j] = objFilter[j];
            }
        }
        return objNew;
    }

    //从数组中摘出需要的字段
    this.array_column = function (arr, val, key = '') {
        let data = {};
        if (key && val) {
            for (let i = 0; i < arr.length; i++) {
                let k = arr[i][key];
                let v = arr[i][val];
                data[k] = v;
            }
        } else if (key && val == null) {
            for (let i = 0; i < arr.length; i++) {
                let k = arr[i][key];
                data[k] = arr[i];
            }
        } else {
            for (let i = 0; i < arr.length; i++) {
                data.push(arr[i][val]);
            }
        }

        return data;
    }
}


/**
 * // 1.初始化
 var et = new zbEditTable();
 et.onClickInput = function() {
        console.log(this); //被点击的input框
    }

 //2. 创建可以编辑的表格
 let domTable = et.createEditTable({
      id:'table_basic', //表格ID
      thead:{
        values:['表名', '引擎', '默认编码', '字符集', '注释'], //表头
      },
      tbody:{
        values:[
            ['aaa','InnoDB','utf8','utf8_general_ci', '注释'], //表格数据
        ],
      }
    });
 document.getElementById('basic_info').appendChild(domTable); //显示到html中

 //批量更新input标签的属性
 // coordinate: 坐标 (0,0: 表示第1行, 第一列;  ',0': 表示所有行的第一列;  '0,': 表示第一行的所有列)
 // attrs: 属性列表
 et.setInputAttributes('table_basic', [
 {coordinate:'0,0', attrs:{name:'table_name'}},
 {coordinate:'0,1', attrs:{name:'engine'}},
 {coordinate:'0,2', attrs:{name:'charset'}},
 {coordinate:'0,3', attrs:{name:'collate'}},
 {coordinate:'0,4', attrs:{name:'table_comment'}},
 ]);

 //3. 创建可以动态增加/删除行的表格
 let domFields = et.createResponseEditTable({
      id:'field_list',
      thead:{
        values:['字段名','数据类型','是否主键','是否允许NULL','默认值','额外设置','注释']
      },
      tbody: {
        default_row:field_list.length,
        default_col:7,
        values:[[...]]
      }
    });
 document.getElementById('fields_info').appendChild(domFields);
 et.setInputAttributes('field_list', [
 {coordinate:',0', attrs:{name:'a'}},
 {coordinate:',1', attrs:{name:'b'}},
 {coordinate:',2', attrs:{name:'c'}},
 {coordinate:',3', attrs:{name:'d'}},
 {coordinate:',4', attrs:{name:'e'}},
 {coordinate:',5', attrs:{name:'f'}},
 {coordinate:',6', attrs:{name:'g'}}
 ]);
 **/