/*
 * Name: skuTable
 * Author: cshaptx4869
 * Project: https://github.com/cshaptx4869/skuTable
 */
layui.define(['jquery', 'form', 'upload', 'layer', 'sortable'], function (exports) {
    "use strict";
    var $ = layui.jquery,
        form = layui.form,
        upload = layui.upload,
        layer = layui.layer,
        sortable = layui.sortable,
        MOD_NAME = 'skuTable';

    //工具类
    class Util {

        static config = {
            shade: [0.02, '#000'],
            time: 2000
        };

        static msg = {
            // 成功消息
            success: function (msg, callback = null) {
                return layer.msg(msg, {
                    icon: 1,
                    shade: Util.config.shade,
                    scrollbar: false,
                    time: Util.config.time,
                    shadeClose: true
                }, callback);
            },
            // 失败消息
            error: function (msg, callback = null) {
                return layer.msg(msg, {
                    icon: 2,
                    shade: Util.config.shade,
                    scrollbar: false,
                    time: Util.config.time,
                    shadeClose: true
                }, callback);
            },
            // 警告消息框
            alert: function (msg, callback = null) {
                return layer.alert(msg, {end: callback, scrollbar: false});
            },
            // 对话框
            confirm: function (msg, ok, no) {
                var index = layer.confirm(msg, {title: '操作确认', btn: ['确认', '取消']}, function () {
                    typeof ok === 'function' && ok.call(this);
                    Util.msg.close(index);
                }, function () {
                    typeof no === 'function' && no.call(this);
                    Util.msg.close(index);
                });
                return index;
            },
            // 消息提示
            tips: function (msg, callback = null) {
                return layer.msg(msg, {
                    time: Util.config.time,
                    shade: Util.config.shade,
                    end: callback,
                    shadeClose: true
                });
            },
            // 加载中提示
            loading: function (msg, callback = null) {
                return msg ? layer.msg(msg, {
                    icon: 16,
                    scrollbar: false,
                    shade: Util.config.shade,
                    time: 0,
                    end: callback
                }) : layer.load(2, {time: 0, scrollbar: false, shade: Util.config.shade, end: callback});
            },
            // 输入框
            prompt: function (option, callback = null) {
                return layer.prompt(option, callback);
            },
            // 关闭消息框
            close: function (index) {
                return layer.close(index);
            }
        };

        static request = {
            post: function (option, ok, no, ex) {
                return Util.request.ajax('post', option, ok, no, ex);
            },
            get: function (option, ok, no, ex) {
                return Util.request.ajax('get', option, ok, no, ex);
            },
            ajax: function (type, option, ok, no, ex) {
                type = type || 'get';
                option.url = option.url || '';
                option.data = option.data || {};
                option.statusName = option.statusName || 'code';
                option.statusCode = option.statusCode || 200;
                ok = ok || function (res) {
                };
                no = no || function (res) {
                    var msg = res.msg == undefined ? '返回数据格式有误' : res.msg;
                    Util.msg.error(msg);
                    return false;
                };
                ex = ex || function (res) {
                };
                if (option.url == '') {
                    Util.msg.error('请求地址不能为空');
                    return false;
                }

                var index = Util.msg.loading('加载中');
                $.ajax({
                    url: option.url,
                    type: type,
                    contentType: "application/x-www-form-urlencoded; charset=UTF-8",
                    dataType: "json",
                    data: option.data,
                    timeout: 60000,
                    success: function (res) {
                        Util.msg.close(index);
                        if (res[option.statusName] == option.statusCode) {
                            return ok(res);
                        } else {
                            return no(res);
                        }
                    },
                    error: function (xhr, textstatus, thrown) {
                        Util.msg.error('Status:' + xhr.status + '，' + xhr.statusText + '，请稍后再试！', function () {
                            ex(xhr);
                        });
                        return false;
                    }
                });
            }
        };

        static tool = {
            uuid: function uuid(randomLength = 8) {
                return Number(Math.random().toString().substr(2, randomLength) + Date.now()).toString(36)
            }
        }
    }

    class SkuTable {
        options = {
            isAttributeValue: 0, //规格类型 0统一规格 1多规格
            isAttributeElemId: 'fairy-is-attribute', //规格类型容器id
            specTableElemId: 'fairy-spec-table', //规格表容器id
            skuTableElemId: 'fairy-sku-table', //SKU表容器id
            rowspan: false, //是否开启SKU行合并,
            sortable: false, //规格拖拽排序
            skuIcon: 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAAAXNSR0IArs4c6QAAAlJJREFUaEPtWsFuwjAMtfMl7LyP2PgBGLsBB4qq/cMuE0K77B+mCjgAl0lAP2CCf9h9fMiWTA4zKtVGm6quyAan0jqOXxw7tmOE1C+arwZgIABj1mH3pk+fn+eLa6WxtyNVm7DbGNNTNI0DAH1Fz1qZyV27tbbviYc2teT70XxR0xoHPJ1SZthvt7YHvBVuw3ZzmOYNCrfMj2ViPpgG4Nt/CyCard4V6jqtiA8ASJsfADXSOLIK06o5dSAEghYcaR8r9bn2ZfXTC/s3bODUt8sx+ZBcHrsu34CQ7BhNlyPfDJgXmmT3HwC5UT5By95C0XTxQDzDbuuxbN4cIYh6oWgWvwAYFXaatxIAiKfXAOgwE7UBaQ2IG/EZQIbhWA1IGdcuypU1YmsDPgOwXojDUgkg0hqgM+zshY5pTloD/rtRikYl9v4+WhT2Qk6hBKWeiPBdWskH2wBcGmNQIb7lG7GjMgYmXLrJGmeT+jzRaLIGlMV0/x1VnQAgmNfcY1I1pqM2RosqmdCcjThPKCGZE8trIA7+jxdyMcJK3ajXW0i6rCJvA8JllYoAxEHeU8/VDqQB2HDaVSgXemkATrGQi+BVeaFzWSVLK9EsvieasNN4yqIt8l08oSkilMsYC0AyqXcRpgiteFmliFCuY3InNK6Mq6JHuiMOO82LqiYsc579FVOyTaDMCaR5eW3EfFsvGkpIa+AglOBuklPeTlZGUD0NesOVlAMNMIE9PfdtL8sRrySDS7fO/NiW81vrTLJd54e2HJqL9jbPecDbqBEgjJP32l9ET+BPTubEMwAAAABJRU5ErkJggg==',
            uploadUrl: '',
            requestSuccessCode: 1, //请求成功返回状态码值
            specDataDelete: true, //开启规格删除
            productId: '', //商品id 配合specDataUrl和skuDataUrl使用
            specData: [], //规格数据
            specDataUrl: '', //优先级大于specData
            skuData: {}, //SKU数据
            skuDataUrl: '', //优先级大于skuData
            skuNameType: 0,
            skuNameDelimiter: '-',
            //统一规格配置项
            singleSkuTableConfig: {
                thead: [
                    {title: '销售价(元)', icon: 'layui-icon-cols'},
                    {title: '市场价(元)', icon: 'layui-icon-cols'},
                    {title: '成本价(元)', icon: 'layui-icon-cols'},
                    {title: '库存', icon: 'layui-icon-cols'},
                    {title: '状态', icon: ''},
                ],
                tbody: [
                    {type: 'input', field: 'price', value: '', verify: 'required|number', reqtext: '销售价不能为空'},
                    {type: 'input', field: 'market_price', value: '0', verify: 'required|number', reqtext: '市场价不能为空'},
                    {type: 'input', field: 'cost_price', value: '0', verify: 'required|number', reqtext: '成本价不能为空'},
                    {type: 'input', field: 'stock', value: '0', verify: 'required|number', reqtext: '库存不能为空'},
                    {type: 'select', field: 'status', option: [{key: '启用', value: '1'}, {key: '禁用', value: '0'}], verify: 'required', reqtext: '状态不能为空'},
                ]
            },
            //多规格配置项
            multipleSkuTableConfig: {
                thead: [
                    {title: '图片', icon: ''},
                    {title: '销售价(元)', icon: 'layui-icon-cols'},
                    {title: '市场价(元)', icon: 'layui-icon-cols'},
                    {title: '成本价(元)', icon: 'layui-icon-cols'},
                    {title: '库存', icon: 'layui-icon-cols'},
                    {title: '状态', icon: ''},
                ],
                tbody: [
                    {type: 'image', field: 'picture', value: '', verify: '', reqtext: ''},
                    {type: 'input', field: 'price', value: '', verify: 'required|number', reqtext: '销售价不能为空'},
                    {type: 'input', field: 'market_price', value: '0', verify: 'required|number', reqtext: '市场价不能为空'},
                    {type: 'input', field: 'cost_price', value: '0', verify: 'required|number', reqtext: '成本价不能为空'},
                    {type: 'input', field: 'stock', value: '0', verify: 'required|number', reqtext: '库存不能为空'},
                    {
                        type: 'select',
                        field: 'status',
                        option: [{key: '启用', value: '1'}, {key: '禁用', value: '0'}],
                        verify: '',
                        reqtext: ''
                    },
                ]
            }
        };

        constructor(options) {
            this.options = $.extend(this.options, options);
            if (this.options.skuDataUrl && this.options.productId) {
                Util.request.get({
                    url: this.options.skuDataUrl,
                    data: {
                        product_id: this.options.productId
                    },
                    statusCode: this.options.requestSuccessCode
                }, (res) => {
                    this.options.skuData = res.data;
                    this.css();
                    this.render();
                    this.listen();
                });
            } else {
                this.css();
                this.render();
                this.listen();
            }
        }

        css() {
            $('head').append(`<style>
                ${this.options.sortable ? `#${this.options.specTableElemId} tbody tr {cursor: move;transition:unset;-webkit-transition:unset;}` : ''}
				#${this.options.specTableElemId} tbody tr td .fairy-spec {
					display: flex;
					align-items: center;
				}
				#${this.options.specTableElemId} tbody tr td .fairy-spec i.layui-icon-delete {
					font-size: 18px;
					color: #cc0000;
					cursor: pointer;
					margin-right: 5px;
				}
				#${this.options.specTableElemId} tbody tr td.fairy-spec-value {
					padding: 10px 15px 0;
				}
				#${this.options.specTableElemId} tbody tr td.fairy-spec-value .list-spec-cell {
					display: flex;
					align-items: center;
					flex-wrap: wrap;
				}
                #${this.options.specTableElemId} tbody tr td.fairy-spec-value .list-spec-cell .spec-tags {
					font-size: 12px;
					background: #ecf5ff;
					border: 1px solid #d9ecff;
					border-radius: 4px;
					margin: 0 10px 10px 0;
					padding: 4px 4px 2px 0;
				}
				#${this.options.specTableElemId} tbody tr td.fairy-spec-value .list-spec-cell .spec-tags i.layui-icon {
					font-size: 12px;
					cursor: pointer;
				}
				#${this.options.specTableElemId} tbody tr td.fairy-spec-value .list-spec-cell .spec-tags div.layui-form-checkbox {
					padding: 0;
				}
				#${this.options.specTableElemId} tbody tr td.fairy-spec-value .list-spec-cell .spec-tags div.layui-form-checkbox > div {
					padding: 0 8px;
				}
                #${this.options.specTableElemId} tbody tr td.fairy-spec-value div.layui-form-checkbox i.layui-icon-ok {
					display: none;
                }
				#${this.options.specTableElemId} tbody tr td div.fairy-spec-value-create {
					margin: 0 0 10px;
				}
                #${this.options.specTableElemId} tbody tr td div.fairy-spec-value-create,
                #${this.options.specTableElemId} tfoot tr td div.fairy-spec-create {
					display: inline-block;
					color: #1E9FFF;
					vertical-align: middle;
					padding: 4px 6px;
                }
                #${this.options.specTableElemId} tfoot tr td div.layui-form-checkbox {
					margin-top: 0;
                }
                #${this.options.specTableElemId} tfoot tr td div.layui-form-checkbox > span{
					color: #1E9FFF;
                }
                #${this.options.skuTableElemId} tbody tr td > img.fairy-sku-img{
					width: 46px;
					height: 46px;
					vertical-align: middle;
					border-radius: 4px;
					overflow: hidden;
                }
                #${this.options.specTableElemId} tbody tr td > i.layui-icon-close,
                #${this.options.specTableElemId} tbody tr td div.fairy-spec-value-create,
                #${this.options.specTableElemId} tfoot tr td div.fairy-spec-create,
                #${this.options.skuTableElemId} thead tr th > i.layui-icon,
                #${this.options.skuTableElemId} tbody tr td > img.fairy-sku-img {
					cursor: pointer;
                }
                </style>`
            );
        }

        listen() {
            var that = this;

            /**
             * 监听规格类型选择
             */
            form.on('radio(fairy-is-attribute)', function (data) {
                that.options.isAttributeValue = data.value;
                that.render();
            });

            /**
             * 监听规格表是否开启删除
             */
            form.on('checkbox(fairy-spec-delete-filter)', function (data) {
                that.options.specDataDelete = data.elem.checked;
                if (data.elem.checked) {
                    $(`#${that.options.specTableElemId} tbody tr i.layui-icon-close`).removeClass('layui-hide');
                } else {
                    $(`#${that.options.specTableElemId} tbody tr i.layui-icon-close`).addClass('layui-hide')
                }
            });

            /**
             * 监听批量赋值
             */
            $(document).off('click', `#${this.options.skuTableElemId} thead tr th i`).on('click', `#${this.options.skuTableElemId} thead tr th i`, function () {
                var thisI = this;
                Util.msg.prompt({title: $(thisI).parent().text().trim() + '批量赋值'}, function (value, index, elem) {
                    $.each($(`#${that.options.skuTableElemId} tbody tr`), function () {
                        var index = that.options.rowspan ?
                            $(thisI).parent().index() - ($(`#${that.options.skuTableElemId} thead th.fairy-spec-name`).length - $(this).children('td.fairy-spec-value').length) :
                            $(thisI).parent().index();
                        $(this).find('td').eq(index).children('input').val(value);
                    });
                    Util.msg.close(index);
                });
            });

            /**
             * 监听添加规格
             */
            $(document).off('click', `#${this.options.specTableElemId} .fairy-spec-create`).on('click', `#${this.options.specTableElemId} .fairy-spec-create`, function () {
                layer.prompt({title: '规格'}, function (value, index, elem) {
                    var specTitleArr = [];
                    $.each(that.options.specData, function (k, v) {
                        specTitleArr.push(v.title)
                    })
                    if (specTitleArr.includes(value)) {
                        Util.msg.error('规格名已存在');
                    } else {
                        that.options.specData.push({id: Util.tool.uuid(), title: value, child: []});
                        that.resetRender(that.options.specTableElemId);
                        that.renderSpecTable();
                    }
                    Util.msg.close(index);
                });
            });

            /**
             * 监听添加规格值
             */
            $(document).off('click', `#${this.options.specTableElemId} .fairy-spec-value-create`).on('click', `#${this.options.specTableElemId} .fairy-spec-value-create`, function () {
                var specId = $(this).parents('td').prev().data('spec-id');

                // 查找当前规格child所在的数组索引
                let specArr = that.options.specData;
                const specIndex = specArr.findIndex(item => item.id == specId);
                // 获取当前规格中所有的规格值
                const childArr = specArr[specIndex].child.map(item => item.title);

                layer.prompt({title: '规格值'}, function (value, index, elem) {
                    if (childArr.includes(value)) {
                        Util.msg.error('规格值已存在');
                    } else {
                        that.options.specData.forEach(function (v, i) {
                            if (v.id == specId) {
                                v.child.push({id: Util.tool.uuid(), title: value, checked: true});
                            }
                        });
                        that.resetRender([that.options.specTableElemId, that.options.skuTableElemId]);
                        that.renderSpecTable();
                        that.renderMultipleSkuTable();
                    }
                    Util.msg.close(index);
                });
            });

            /**
             * 监听删除规格/规格值
             */
            $(document).off('click', `#${this.options.specTableElemId} i.del-btn`).on('click', `#${this.options.specTableElemId} i.del-btn`, function () {
                if (typeof $(this).attr('data-spec-index') !== "undefined") {
                    var index = $(this).data('spec-index');
                    Util.msg.confirm('确认要删除此规格吗？', function(){
                        that.options.specData.splice(index, 1);
                        that.resetRender([that.options.specTableElemId, that.options.skuTableElemId]);
                        that.renderSpecTable();
                        that.renderMultipleSkuTable();
                    })
                } else if (typeof $(this).attr('data-spec-value-index') !== "undefined") {
                    var [i, ii] = $(this).data('spec-value-index').split('-');
                    Util.msg.confirm('确认要删除此规格值吗？', function(){
                        that.options.specData[i].child.splice(ii, 1);
                        that.resetRender([that.options.specTableElemId, that.options.skuTableElemId]);
                        that.renderSpecTable();
                        that.renderMultipleSkuTable();
                    })
                }
            });

            /**
             * 图片移入放大/移出恢复
             */
            var imgLayerIndex = null;
            $(document).off('mouseenter', '.fairy-sku-img').on('mouseenter', '.fairy-sku-img', function () {
                if($(this).attr('src') == that.options.skuIcon) return
                imgLayerIndex = layer.tips('<img src="' + $(this).attr('src') + '" style="max-width:200px;"  alt=""/>', this, {
                    // tips: [2, 'rgba(41,41,41,.5)'],
                    time: 0
                });
            })
            $(document).off('mouseleave', '.fairy-sku-img').on('mouseleave', '.fairy-sku-img', function () {
                layer.close(imgLayerIndex);
            })
        }

        /**
         * 渲染
         */
        render() {
            this.resetRender();
            this.renderIsAttribute(this.options.isAttributeValue);
            if (this.options.isAttributeValue == '1') {
                if (this.options.specDataUrl && this.options.productId) {
                    Util.request.get({
                        url: this.options.specDataUrl,
                        productId: this.options.productId,
                        statusCode: this.options.requestSuccessCode
                    }, (res) => {
                        this.options.specData = res.data;
                        this.renderSpecTable();
                        this.renderMultipleSkuTable();
                    });
                } else {
                    this.renderSpecTable();
                    this.renderMultipleSkuTable();
                }
            } else {
                this.renderSingleSkuTable();
            }
        }

        /**
         * 重新渲染
         * @param targets
         */
        resetRender(targets) {
            if (typeof targets === 'string') {
                $(`#${targets}`).parents('.form-group').replaceWith(`<div id="${targets}"></div>`);
            } else if ($.isArray(targets) && targets.length) {
                targets.forEach((item) => {
                    $(`#${item}`).parents('.form-group').replaceWith(`<div id="${item}"></div>`);
                })
            } else {
                $(`#${this.options.isAttributeElemId}`).parents('.form-group').replaceWith(`<div id="${this.options.isAttributeElemId}"></div>`);
                $(`#${this.options.specTableElemId}`).parents('.form-group').replaceWith(`<div id="${this.options.specTableElemId}"></div>`);
                $(`#${this.options.skuTableElemId}`).parents('.form-group').replaceWith(`<div id="${this.options.skuTableElemId}"></div>`);
            }
        }

        /**
         * 渲染规格类型
         * @param checkedValue
         */
        renderIsAttribute(checkedValue) {
            var html = '';
            html += `<input type="radio" name="is_attribute" title="统一规格" value="0" lay-filter="fairy-is-attribute" ${checkedValue == '0' ? 'checked' : ''}>`;
            html += `<input type="radio" name="is_attribute" title="多规格" value="1" lay-filter="fairy-is-attribute" ${checkedValue == '1' ? 'checked' : ''}>`;
            this.renderFormItem('规格类型', html, this.options.isAttributeElemId);
        }

        renderSingleSkuTable() {
            var that = this,
                table = `<table class="layui-table" id="${this.options.skuTableElemId}">`;
            table += '<thead>';
            table += '<tr>';
            this.options.singleSkuTableConfig.thead.forEach((item) => {
                table += `<th>${item.title}</th>`;
            });
            table += '</tr>';
            table += '</thead>';

            table += '<tbody>';
            table += '<tr>';
            that.options.singleSkuTableConfig.tbody.forEach(function (item) {
                switch (item.type) {
                    case "select":
                        table += '<td>';
                        table += `<select name="${item.field}" lay-verify="${item.verify}" lay-reqtext="${item.reqtext}">`;
                        item.option.forEach(function (o) {
                            table += `<option value="${o.value}" ${that.options.skuData[item.field] == o.value ? 'selected' : ''}>${o.key}</option>`;
                        });
                        table += '</select>';
                        table += '</td>';
                        break;
                    case "input":
                    default:
                        table += '<td>';
                        table += `<input type="text" name="${item.field}" value="${that.options.skuData[item.field] !== undefined ? that.options.skuData[item.field] : item.value}" class="layui-input" lay-verify="${item.verify}" lay-reqtext="${item.reqtext}">`;
                        table += '</td>';
                        break;
                }
            });
            table += '</tr>';
            table += '<tbody>';
            table += '</table>';

            this.renderFormItem('SKU', table, this.options.skuTableElemId);
        }

        /**
         * 渲染规格表
         */
        renderSpecTable() {
            var that = this,
                table = `<table class="layui-table" id="${this.options.specTableElemId}"><thead><tr><th>规格名</th><th>规格值</th></tr></thead><colgroup><col width="140"></colgroup><tbody>`;
            $.each(this.options.specData, function (index, item) {
                table += that.options.sortable ? `<tr data-id="${item.id}">` : '<tr>';
                table += `<td data-spec-id="${item.id}"><div class="fairy-spec"><i class="layui-icon layui-icon-delete layui-anim layui-anim-scale del-btn ${that.options.specDataDelete ? '' : 'layui-hide'}" data-spec-index="${index}"></i>${item.title}</div></td>`;
                table += '<td class="fairy-spec-value"><div class="list-spec-cell">';
                $.each(item.child, function (key, value) {
                    table += '<div class="spec-tags">'
                    table += `<input type="checkbox" title="${value.title}" lay-filter="fairy-spec-filter" value="${value.id}" checked} /><i class="layui-icon layui-icon-close layui-anim layui-anim-scale del-btn ${that.options.specDataDelete ? '' : 'layui-hide'}" data-spec-value-index="${index}-${key}"></i> `;
                    table += '</div>'
                });
                table += '<div class="fairy-spec-value-create"><i class="layui-icon layui-icon-addition"></i>规格值</div>'
                table += '</div></td>';
                table += '</tr>';
            });
            table += '</tbody>';

            table += '<tfoot><tr><td colspan="2">';
            table += `<div class="fairy-spec-create"><i class="layui-icon layui-icon-addition"></i>规格</div>`;
            table += '</td></tr></tfoot>';
            table += '</table>';

            this.renderFormItem('商品规格', table, this.options.specTableElemId);

            if (this.options.sortable) {
                /**
                 * 拖拽
                 */
                var sortableObj = sortable.create($(`#${this.options.specTableElemId} tbody`)[0], {
                    animation: 1000,
                    onEnd: (evt) => {
                        //获取拖动后的排序
                        var sortArr = sortableObj.toArray(),
                            sortSpecData = [];
                        this.options.specData.forEach((item) => {
                            sortSpecData[sortArr.indexOf(String(item.id))] = item;
                        });
                        this.options.specData = sortSpecData;
                        this.resetRender(that.options.skuTableElemId);
                        this.renderMultipleSkuTable();
                    },
                });
            }
        }

        /**
         * 渲染sku表
         */
        renderMultipleSkuTable() {
            var that = this, table = `<table class="layui-table" id="${this.options.skuTableElemId}">`;

            if ($(`#${this.options.specTableElemId} tbody input[type=checkbox]`).length) {
                var prependThead = [], prependTbody = [];
                $.each(this.options.specData, function (index, item) {
                    prependThead.push(item.title);
                    var prependTbodyItem = [];
                    $.each(item.child, function (key, value) {
                        prependTbodyItem.push({id: value.id, title: value.title});
                    });
                    prependTbody.push(prependTbodyItem);
                });

                table += '<colgroup>' + '<col width="70">'.repeat(prependThead.length + 1) + '</colgroup>';

                table += '<thead>';
                if (prependThead.length > 0) {
                    var theadTr = '<tr>';

                    theadTr += prependThead.map(function (t, i, a) {
                        return '<th class="fairy-spec-name">' + t + '</th>';
                    }).join('');

                    this.options.multipleSkuTableConfig.thead.forEach(function (item) {
                        theadTr += '<th>' + item.title + (item.icon ? ' <i class="layui-icon ' + item.icon + '"></i>' : '') + '</th>';
                    });

                    theadTr += '</tr>';

                    table += theadTr;
                }
                table += '</thead>';

                if (this.options.rowspan) {
                    var skuRowspanArr = [];
                    prependTbody.forEach(function (v, i, a) {
                        var num = 1, index = i;
                        while (index < a.length - 1) {
                            num *= a[index + 1].length;
                            index++;
                        }
                        skuRowspanArr.push(num);
                    });
                }

                var prependTbodyTrs = [];
                prependTbody.reduce(function (prev, cur, index, array) {
                    var tmp = [];
                    prev.forEach(function (a) {
                        cur.forEach(function (b) {
                            tmp.push({id: a.id + that.options.skuNameDelimiter + b.id, title: a.title + that.options.skuNameDelimiter + b.title});
                        })
                    });
                    return tmp;
                }).forEach(function (item, index, array) {
                    var tr = '<tr>';

                    tr += item.title.split(that.options.skuNameDelimiter).map(function (t, i, a) {
                        if (that.options.rowspan) {
                            if (index % skuRowspanArr[i] === 0 && skuRowspanArr[i] > 1) {
                                return '<td class="fairy-spec-value" rowspan="' + skuRowspanArr[i] + '">' + t + '</td>';
                            } else if (skuRowspanArr[i] === 1) {
                                return '<td class="fairy-spec-value">' + t + '</td>';
                            } else {
                                return '';
                            }
                        } else {
                            return '<td>' + t + '</td>';
                        }
                    }).join('');

                    that.options.multipleSkuTableConfig.tbody.forEach(function (c) {
                        switch (c.type) {
                            case "image":
                                tr += '<td><input type="hidden" name="' + that.makeSkuName(item, c) + '" value="' + (that.options.skuData[that.makeSkuName(item, c)] ? that.options.skuData[that.makeSkuName(item, c)] : c.value) + '" lay-verify="' + c.verify + '" lay-reqtext="' + c.reqtext + '"><img class="fairy-sku-img" src="' + (that.options.skuData[that.makeSkuName(item, c)] ? that.options.skuData[that.makeSkuName(item, c)] : that.options.skuIcon) + '" alt="' + c.field + '图片"></td>';
                                break;
                            case "select":
                                tr += '<td><select name="' + that.makeSkuName(item, c) + '" lay-verify="' + c.verify + '" lay-reqtext="' + c.reqtext + '">';
                                c.option.forEach(function (o) {
                                    tr += '<option value="' + o.value + '" ' + (that.options.skuData[that.makeSkuName(item, c)] == o.value ? 'selected' : '') + '>' + o.key + '</option>';
                                });
                                tr += '</select></td>';
                                break;
                            case "input":
                            default:
                                tr += '<td><input type="text" name="' + that.makeSkuName(item, c) + '" value="' + (that.options.skuData[that.makeSkuName(item, c)] !== undefined ? that.options.skuData[that.makeSkuName(item, c)] : c.value) + '" class="layui-input" lay-verify="' + c.verify + '" lay-reqtext="' + c.reqtext + '"></td>';
                                break;
                        }
                    });
                    tr += '</tr>';

                    tr && prependTbodyTrs.push(tr);
                });

                table += '<tbody>';
                if (prependTbodyTrs.length > 0) {
                    table += prependTbodyTrs.join('');
                }
                table += '</tbody>';

            } else {
                table += '<thead></thead><tbody></tbody><tfoot><tr><td>请先完善规格值</td></tr></tfoot>';
            }

            table += '</table>';

            if(!this.options.specData.length) return
            this.renderFormItem('SKU', table, this.options.skuTableElemId);

            //上传
            if (this.options.uploadUrl) {
                upload.render({
                    elem: '.fairy-sku-img',
                    url: this.options.uploadUrl,
                    exts: 'png|jpg|ico|jpeg|gif',
                    accept: 'images',
                    acceptMime: 'image/*',
                    multiple: false,
                    done: function (res) {
                        if (res.code === that.options.requestSuccessCode) {
                            var url = res.data.url;
                            $(this.item).attr('src', url).prev().val(url);
                            Util.msg.success(res.msg);
                        } else {
                            var msg = res.msg == undefined ? '返回数据格式有误' : res.msg;
                            Util.msg.error(msg);
                        }
                        return false;
                    }
                });
            }
        }

        /**
         * 渲染表单项
         * @param label 标题
         * @param content 内容
         * @param target id
         * @param isRequired
         */
        renderFormItem(label, content, target, isRequired = true) {
            var html = '';
            html += '<div class="form-group">';
            html += `<label class="col-sm-2 control-label">`;
            if(isRequired){
                html += '<span class="form-required">*</span>';
            }
            html += `${label.length ? label : ''}`;
            html += '</label>';
            html += '<div class="col-md-6 col-sm-10">';
            html += content;
            html += '</div>';
            html += '</div>';
            $(`#${target}`).replaceWith(html);
            form.render();
        }

        makeSkuName(sku, conf) {
            return 'skus[' + (this.options.skuNameType === 0 ? sku.id : sku.title) + '][' + conf.field + ']';
        }

        getSpecData() {
            return this.options.specData;
        }

        getFormFilter() {
            var fariyForm = $('form.fairy-form');
            if (!fariyForm.attr('lay-filter')) {
                fariyForm.attr('lay-filter', 'fairy-form-filter');
            }
            return fariyForm.attr('lay-filter');
        }

        getFormSkuData() {
            var skuData = {};
            $.each(form.val(this.getFormFilter()), function (key, value) {
                if (key.startsWith('skus')) {
                    skuData[key] = value;
                }
            });
            return skuData;
        }

    }

    exports(MOD_NAME, {
        render: function (options) {
            return new SkuTable(options);
        }
    })
});
