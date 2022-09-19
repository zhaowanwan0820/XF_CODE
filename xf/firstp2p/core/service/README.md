# Services的编写规则

## 仅包含公开接口

service层的接口，主要提供给web、api、admin等的controllers通过RPC调用, 因此**RPC用不到的类以及方法，请写到dao层**

## 不直接执行sql

为维护方便，应将带有sql语句的方法写到dao层,并在service层对dao进行上层封装后供外部调用

## 相关类都应继承BaseService类

## 不可进行平级调用

service层的各个平级的类不允许互相直接调用，如果有公共的处理逻辑，请写进dao层

## 类名
所有类名以Model结尾, 例如：DealModel
