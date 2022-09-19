{% extends "view/main.volt" %}
{% block left %}
    <ul class="parent-menu-list">
        <li>
            <a href="#">属性列表</a>
            <ul class="child-menu-list">
                {% for prop in reflectionClass.getProperties() %}
                    {% if reflectionClass.getName() == prop.class %}
                    <li><a href="#">${{prop.name}}</a></li>
                    {% endif %}
                {% endfor %}
            </ul>
        </li>
        <li>
            <a href="#">方法列表</a>
            <ul class="child-menu-list">
                {% for method in reflectionClass.getMethods() %}
                    {% if substr(method.name, 0, 2) == '__'  %}
                        {% continue %}
                    {% endif %}
                    <li><a href="#method-{{ method.name }}">{{ method.name }}</a></li>
                {% endfor %}
            </ul>
        </li>
    </ul>
{% endblock %}

{% block right %}
    <h2>Class {{ class }}</h2>
    {% if reflectionClass.getParentClass() %}
        <ul class="child-menu-list">
            <li>
                {% set parentClass = reflectionClass.getParentClass().getName() %}
                <a href="?class={{ str_replace('\\', '_', parentClass) }}">{{ parentClass }}</a>
            </li>
        </ul>
    {% endif %}

    <h3 class="title">常量列表</h3>
    <table class="doctable informaltable" style ="margin:10px 10px; width=90%">
        <thead>
            <tr>
                <th>名称</th>
                <th>值</th>
                <th>说明</th>
            </tr>
        </thead>

        <tbody class="tbody">
            {% for name, value in reflectionClass.getConstants() %}
                <tr>
                    <td>{{ name }}</td>
                    <td>{{ value }}</td>
                    <td>{{ constReflection.getDocComment(name) }}</td>
                </tr>
            {% endfor %}
            <tr><td colspan=3>&nbsp;</td></tr>
        </tbody>

    </table>

    <h3 class="title">属性列表</h3>
    <table class="doctable informaltable" style ="margin:10px 10px; width=90%">
        <thead>
            <tr>
                <th>名称</th>
                <th>类型</th>
                <th>是否必须</th>
                <th>说明</th>
            </tr>
        </thead>

        <tbody class="tbody">
            {% for prop in reflectionClass.getProperties() %}
                {% if reflectionClass.getName() == prop.class %}
                <?php
                    try {
                        $annotations = $reader->getPropertyAnnotations($prop->class, $prop->name);
                    } catch(\Exception $e) {}
                    $required = $annotations->get('required') == true ? '是':'否';
                    $var = $annotations->get('var');
                ?>
                <tr>
                    <?php $propModifier = implode(" ", \Reflection::getModifierNames($prop->getModifiers())); ?>
                    <td><span style="color:#693">{{ propModifier }}</span> ${{ prop.name }}</td>
                    <td>{{ var }}</td>
                    <td>{{ required }}</td>
                    <td>{{ str_replace(['/', '*'], "", strstr(prop.getDocComment(), '@', true)) }}</td>
                </tr>
                {% endif %}
            {% endfor %}
            <tr><td colspan=4>&nbsp;</td></tr>
        </tbody>
    </table>

    <h3 class="title">方法列表</h3>

    {% for method in methods %}
    <div class="refsect1 description" id="method-{{ method['name'] }}">
        <div class="methodsynopsis dc-description">
            <pre>{{ method['docComment'] }}</pre>
            <span class="modifier">{{ method['modifier'] }}</span>
            <span class="type">{{ method['methodReturn'] }}</span>
            <span class="methodname"><strong>{{ method['name'] }}</strong></span>
            ( <span class="methodparam">{{ method['paramStr'] }}</span> ) {{ method['inherit'] }}
        </div>
    </div>
    {% endfor %}
{% endblock %}
