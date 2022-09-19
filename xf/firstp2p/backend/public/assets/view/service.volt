{% extends "view/main.volt" %}
{% block left %}
    <ul class="parent-menu-list">
        <li>
            <a href="#">服务列表</a>
            <ul class="child-menu-list">
                {% for file in serviceFileList %}
                    {% set classname = pathinfo(file, constant("PATHINFO_FILENAME")) %}
                    <li{% if targetService is defined and targetService == classname %} class="current"{% endif %}><a href="?service={{ classname }}">{{ classname }}</a></li>
                {% endfor %}

            </ul>
        </li>
    </ul>
{% endblock %}

{% block right %}
    {% if targetService is defined %}
        <h2>Service <a href="spec.php?class={{ targetService }}" title="查看详情">{{ targetService }}</a></h2>
        <h3 class="title">描述</h3>
        <div class="refsect1 description" id="list-{$method->name}">
            <div class="methodsynopsis dc-description">
                <pre>{{ class.getDocComment() }}</pre>
            </div>
        </div>
        <h3 class="title">接口定义</h3>
        {% for method in methods %}
            <div class="refsect1 description" id="method-{{ method['name'] }}">
                <div class="methodsynopsis dc-description">
                    <button class="playground-button" id="play-{{ method['name'] }}" method="{{method['name']}}">示例</button>
                    <pre>{{ method['docComment'] }}</pre>
                    <span class="modifier">{{ method['modifier'] }}</span>
                    <span class="type">{{ method['methodReturn'] }}</span>
                    <span class="methodname"><strong>{{ method['name'] }}</strong></span>
                    ( <span class="methodparam">{{ method['paramStr'] }}</span> )
                </div>
            </div>
            <div class="example" id="example-{{ method['name'] }}" style="display:none;">
                <div class="phpcode">
                    <code>
                        {{ highlight_string(method['sample'], true) }}
                    </code>
                </div>
                <div style="text-align:right">
                    <button>运行</button>
                </div>
            </div>
        {% endfor %}
    {% else %}
        <h2>Welcome to NCFGroup FundRPC Service</h2>
        <pre>
  _   _  _____ ______ _____
 | \ | |/ ____|  ____/ ____|
 |  \| | |    | |__ | |  __ _ __ ___  _   _ _ __
 | . ` | |    |  __|| | |_ | '__/ _ \| | | | '_ \
 | |\  | |____| |   | |__| | | | (_) | |_| | |_) |
 |_| \_|\_____|_|    \_____|_|  \___/ \__,_| .__/
                                           | |
                                           |_|
        </pre>
    {% endif %}
{% endblock %}

{% block js_footer %}
    <style type="text/css">
     .playground-button {
         letter-spacing: 1px;
         font-weight: bold;
         width: 75px;
         height: 24px;
         font-size: 15px;
         line-height: 21px;
         background-color: rgba(0, 0, 0, 0.15);
         border-width: 0;
         opacity: 0.5;
     }
    </style>

    <script type="text/javascript">
     vex.defaultOptions.className='vex-theme-os';
     $(document).ready(function(){
         $(".playground-button").click(function(){
             var example = "#example-" + $(this).attr("method");
             vex.open({
                 content: $(example).html(),
                 afterOpen: function($vexContent) {
                     return $vexContent.append($el);
                 },
                 afterClose: function() {
                     return console.log('vexClose');
                 },
                 // appendLocation: 'section'
             });
         })
     })
    </script>
{% endblock %}
