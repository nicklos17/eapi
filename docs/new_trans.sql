/*==============================================================*/
/* DBMS name:      MySQL 5.0                                    */
/* Created on:     2015-12-1 14:42:39                           */
/*==============================================================*/


drop table if exists new_booking_info;

drop table if exists new_booking_main;

drop table if exists new_expored_domain;

drop table if exists new_record;

drop table if exists new_remind;

drop table if exists new_tao;

drop table if exists new_trans_delay;

drop table if exists new_trans_history;

drop table if exists new_trans_result;

drop table if exists shop;

drop table if exists shop_rate;

/*==============================================================*/
/* Table: new_booking_info                                      */
/*==============================================================*/
create table new_booking_info
(
   i_id                 int not null auto_increment,
   b_id                 int,
   i_domain             varchar(72) not null,
   i_enameId            int not null,
   i_order_id           int not null,
   i_nick_name          tinyint not null default 0 comment '1：使用昵称
            2：不使用',
   i_create_time        int not null,
   i_status             tinyint not null comment '1：直接得标
            2：第一个预订用户转竞价默认领先
            3：已经退款',
   primary key (i_id)
)
type = ISAM
DEFAULT CHARACTER SET = utf8;

/*==============================================================*/
/* Table: new_booking_main                                      */
/*==============================================================*/
create table new_booking_main
(
   b_id                 int not null auto_increment,
   b_domain             varchar(72) not null,
   b_start_time         int not null,
   b_del_time           int not null,
   b_status             tinyint not null comment '1：待抢注
            2：抢注失败
            3：竞价中
            4：竞价结束',
   b_end_time           int not null,
   b_count              int not null comment '有多少人预订',
   b_line_sort          int not null comment '国际域名所在文件行，协助抢注程序',
   primary key (b_id)
)
type = ISAM
DEFAULT CHARACTER SET = utf8;

/*==============================================================*/
/* Table: new_expored_domain                                    */
/*==============================================================*/
create table new_expored_domain
(
   e_id                 int not null auto_increment,
   e_domain             varchar(72) not null,
   e_del_time           int not null,
   e_reg_time           int not null comment '域名原注册时间',
   e_len                tinyint not null,
   e_tld                tinyint not null,
   e_class              tinyint not null comment '同淘域名的结构',
   e_two_class          tinyint not null,
   e_three_class        tinyint not null,
   e_hot                tinyint not null,
   e_type               tinyint not null comment '1：CNNIC管辖的域名
            2：国际域名',
   e_line_sort          int not null comment '域名在删除文件中的顺序 抢注程序需要',
   e_now                tinyint not null comment '1:提前预订
            0:默认',
   primary key (e_id)
)
type = ISAM
DEFAULT CHARACTER SET = utf8;

/*==============================================================*/
/* Table: new_record                                            */
/*==============================================================*/
create table new_record
(
   r_id                 int not null auto_increment,
   r_create_time        int not null,
   r_enameId            int not null,
   r_money              int not null,
   r_order_id           int not null,
   r_ip                 varchar(15) not null,
   r_dn                 varchar(72) not null,
   t_id                 int not null,
   r_order_status       tinyint not null default 0 comment '竞价的保证金订单状态
            1：冻结状态
            2：已经解冻',
   r_trans_type         tinyint not null default 3 comment '3：竞价
            4：竞价(专题拍卖)
            5：竞价(易拍易卖)
            ',
   r_agent_price        int not null default 0 comment '用户提交的代理价格--冗余字段',
   r_source             tinyint not null default 0 comment '出价来源
            1：PC
            2：APP
            3：H5',
   r_flag               tinyint not null default 0 comment '针对同一个交易最后一条出价记录，方便我出价交易功能获取数据',
   primary key (r_id)
)
type = ISAM
DEFAULT CHARACTER SET = utf8;

/*==============================================================*/
/* Table: new_remind                                            */
/*==============================================================*/
create table new_remind
(
   r_id                 int not null auto_increment,
   t_id                 int,
   r_enameId            int not null,
   r_domain             varchar(72) not null,
   r_money              int not null,
   r_end_time           int not null,
   r_is_send            tinyint not null default 1 comment '1：未发送
            2：已发送
            3：取消发送',
   r_call_time          int not null default 0 comment '是否电话通知，如果已经通知保存电话通知的时间',
   primary key (r_id)
)
type = ISAM
DEFAULT CHARACTER SET = utf8;

/*==============================================================*/
/* Table: new_tao                                               */
/*==============================================================*/
create table new_tao
(
   t_id                 int not null auto_increment,
   t_dn                 varchar(72) not null,
   t_body               varchar(64) not null comment '域名主体不要后缀的部分，ES搜索用到',
   t_status             tinyint not null default 1 comment '1：正在交易
            2：等待双方确认（有人购买，竞价）
            3：正在过户（有人购买：一口价）
            4：买家已经确认
            5：卖家已经确认
            6：交易失败买家违约
            7：交易失败卖家违约
            8：交易流拍
            9：卖家取消交易
            10：管理员取消交易
            14：交易成功
            15：下架队列',
   t_type               tinyint not null default 1 comment '1：一口价
            2：竞价
            3：竞价(预订竞价)
            4：竞价(专题拍卖)
            5：竞价(易拍易卖)
            6：一口价格(SEDO)
            8：竞价(拍卖会)
            9：询价(另一张表)',
   t_topic_type         tinyint not null default 1 comment '专题类型的ID 
            1：普通交易 
            2：易拍易卖
            3：专题拍卖 
            5：sedo
            8：拍卖会',
   t_topic              tinyint not null default 0 comment '专题表主键',
   t_enameId            int not null,
   t_buyer              int not null default 0,
   t_start_price        int not null default 0,
   t_nickname           varchar(20) not null default '‘’' comment '一口价购买者随机昵称',
   t_now_price          int not null,
   t_agent_price        int not null default 0,
   t_create_time        int not null default 0,
   t_start_time         int not null default 0,
   t_end_time           int not null default 0 comment '发布交易计算出来的时间',
   t_last_time          int not null default 0 comment 'ES根据这个字段更新数据',
   t_tld                tinyint not null default 1 comment '1:com
            2:cn
            3:.com.cn
            4:net.cn
            5:org.cn
            6:省份.cn
            7:net
            8:org
            9:cc
            10:wang
            11:top
            12:biz
            13:info
            14:asia
            15:me
            16:tv
            17:tw
            18:in
            19:cd
            20:pw
            21:me
            22:中国
            23:公司
            24:网络',
   t_len                tinyint not null default 1,
   t_desc               varchar(200) not null default '‘’',
   t_count              int not null default 0,
   t_money_type         tinyint not null comment '2：不可提现
            3：可提现',
   t_ip                 varchar(15) not null,
   t_buyer_ip           varchar(15) not null default '‘’',
   t_is_our             tinyint not null default 0 comment '1：我司域名
            2：非我司',
   t_exp_time           int not null,
   t_class_name         tinyint not null default 0 comment '1：数字
            2：字母
            3：杂米
            4：中文',
   t_two_class          tinyint not null default 0 comment '1-4：拼（单拼，双拼，三拼，四拼）
            6：声母（2声，3声，4声，5声）
            7：数字
            8：杂（二杂，三杂）
            10：CVCV型
            12：如果同时是CVCV和双拼
            
            ',
   t_three_class        tinyint not null default 0 comment '三级分类，具体见GIT文档
            类似：三数字：AAA, AAB, ABB, ABA',
   t_seller_order       int not null default 0 comment '非我司域名使用出售使用',
   t_complate_time      int not null default 0 comment '买家购买时间',
   t_order_id           int not null default 0 comment '交易成交后的订单ID，一口价就是直接扣钱的订单ID，竞价就是最后过户的订单ID',
   t_people             tinyint not null default 0 comment '预订竞价该域名的预订人数',
   t_hot                tinyint not null default 0 comment '用户自己推荐的域名在BBS展示',
   t_admin_hot          tinyint not null default 0 comment '易拍易卖，专题拍卖管理员推荐域名',
   primary key (t_id)
)
type = InnoDB
DEFAULT CHARACTER SET = utf8
auto_increment = 80000000;

/*==============================================================*/
/* Table: new_trans_delay                                       */
/*==============================================================*/
create table new_trans_delay
(
   d_id                 int not null,
   d_enameId            int not null,
   d_create_time        int not null,
   d_buyer              int not null,
   d_day                tinyint not null,
   d_action_time        int not null,
   d_status             tinyint not null comment '1：申请延期
            2：拒绝延期
            3：同意延期',
   t_id                 int not null,
   d_type               tinyint not null default 0 comment '标示是买家还是卖家申请
            1：卖家
            2：买家',
   primary key (d_id)
)
type = ISAM
DEFAULT CHARACTER SET = utf8;

/*==============================================================*/
/* Table: new_trans_history                                     */
/*==============================================================*/
create table new_trans_history
(
   t_id                 int not null,
   t_dn                 varchar(72) not null,
   t_body               varchar(64) not null comment '域名主体不要后缀的部分，ES搜索用到',
   t_status             tinyint not null default 1 comment '1：正在交易
            2：等待双方确认（有人购买，竞价）
            3：正在过户（有人购买：一口价）
            4：买家已经确认
            5：卖家已经确认
            6：交易失败买家违约
            7：交易失败卖家违约
            8：交易流拍
            9：卖家取消交易
            10：管理员取消交易
            14：交易成功
            15：下架队列',
   t_type               tinyint not null default 1 comment '1：一口价
            2：竞价
            3：竞价(预订竞价)
            4：竞价(专题拍卖)
            5：竞价(易拍易卖)',
   t_topic_type         tinyint not null default 1 comment '专题类型的ID 
            1：普通交易 
            2：易拍易卖
            3：专题拍卖 
            5：sedo
            8：拍卖会',
   t_topic              tinyint not null default 0 comment '专题表主键',
   t_enameId            int not null,
   t_buyer              int not null default 0,
   t_start_price        int not null default 0,
   t_nickname           varchar(20) not null default '‘’' comment '一口价购买者随机昵称',
   t_now_price          int not null,
   t_agent_price        int not null default 0,
   t_create_time        int not null default 0,
   t_start_time         int not null default 0,
   t_end_time           int not null default 0 comment '发布交易计算出来的时间',
   t_last_time          int not null default 0 comment 'ES根据这个字段更新数据',
   t_tld                tinyint not null default 1 comment '1:com
            2:cn
            3:.com.cn
            4:net.cn
            5:org.cn
            6:省份.cn
            7:net
            8:org
            9:cc
            10:wang
            11:top
            12:biz
            13:info
            14:asia
            15:me
            16:tv
            17:tw
            18:in
            19:cd
            20:pw
            21:me
            22:中国
            23:公司
            24:网络',
   t_len                tinyint not null default 1,
   t_desc               varchar(200) not null default '‘’',
   t_count              int not null default 0,
   t_money_type         tinyint not null comment '2：不可提现
            3：可提现',
   t_ip                 varchar(15) not null,
   t_buyer_ip           varchar(15) not null default '‘’',
   t_is_our             tinyint not null default 0 comment '1：我司域名
            2：非我司',
   t_exp_time           int not null,
   t_class_name         tinyint not null default 0 comment '1：数字
            2：字母
            3：杂米
            4：中文',
   t_two_class          tinyint not null default 0 comment '1-4：拼（单拼，双拼，三拼，四拼）
            6：声母（2声，3声，4声，5声）
            7：数字
            8：杂（二杂，三杂）
            10：CVCV型
            12：如果同时是CVCV和双拼
            
            ',
   t_three_class        tinyint not null default 0 comment '三级分类，具体见GIT文档
            类似：三数字：AAA, AAB, ABB, ABA',
   t_seller_order       int not null default 0 comment '非我司域名使用出售使用',
   t_complate_time      int not null default 0 comment '买家购买时间',
   t_order_id           int not null default 0 comment '交易成交后的订单ID，一口价就是直接扣钱的订单ID，竞价就是最后过户的订单ID',
   t_people             tinyint not null default 0 comment '预订竞价该域名的预订人数',
   t_hot                tinyint not null default 0 comment '用户自己推荐的域名在BBS展示',
   t_admin_hot          tinyint not null default 0 comment '易拍易卖，专题拍卖管理员推荐域名',
   primary key (t_id)
)
type = InnoDB
DEFAULT CHARACTER SET = utf8
auto_increment = 80000000;

alter table new_trans_history comment '表结构同淘域名表，这个表中只有交易成功的记录，或者交易了等待双方处理的记录，如果记录写入到这张表就从淘域名表删除';

/*==============================================================*/
/* Table: new_trans_result                                      */
/*==============================================================*/
create table new_trans_result
(
   t_id                 int not null auto_increment,
   t_dn                 varchar(72) not null,
   t_body               varchar(64) not null comment '域名主体不要后缀的部分，ES搜索用到',
   t_status             tinyint not null default 1 comment '1：正在交易
            2：等待双方确认（有人购买，竞价）
            3：正在过户（有人购买：一口价）
            4：买家已经确认
            5：卖家已经确认
            6：交易失败买家违约
            7：交易失败卖家违约
            8：交易流拍
            9：卖家取消交易
            10：管理员取消交易
            14：交易成功
            15：下架队列',
   t_type               tinyint not null default 1 comment '1：一口价
            2：竞价
            3：竞价(预订竞价)
            4：竞价(专题拍卖)
            5：竞价(易拍易卖)',
   t_topic_type         tinyint not null default 1 comment '专题类型的ID 
            1：普通交易 
            2：易拍易卖
            3：专题拍卖 
            5：sedo
            8：拍卖会',
   t_topic              tinyint not null default 0 comment '专题表主键',
   t_enameId            int not null,
   t_buyer              int not null default 0,
   t_start_price        int not null default 0,
   t_nickname           varchar(20) not null default '‘’' comment '一口价购买者随机昵称',
   t_now_price          int not null,
   t_agent_price        int not null default 0,
   t_create_time        int not null default 0,
   t_start_time         int not null default 0,
   t_end_time           int not null default 0 comment '发布交易计算出来的时间',
   t_last_time          int not null default 0 comment 'ES根据这个字段更新数据',
   t_tld                tinyint not null default 1 comment '1:com
            2:cn
            3:.com.cn
            4:net.cn
            5:org.cn
            6:省份.cn
            7:net
            8:org
            9:cc
            10:wang
            11:top
            12:biz
            13:info
            14:asia
            15:me
            16:tv
            17:tw
            18:in
            19:cd
            20:pw
            21:me
            22:中国
            23:公司
            24:网络',
   t_len                tinyint not null default 1,
   t_desc               varchar(200) not null default '‘’',
   t_count              int not null default 0,
   t_money_type         tinyint not null comment '2：不可提现
            3：可提现',
   t_ip                 varchar(15) not null,
   t_buyer_ip           varchar(15) not null default '‘’',
   t_is_our             tinyint not null default 0 comment '1：我司域名
            2：非我司',
   t_exp_time           int not null,
   t_class_name         tinyint not null default 0 comment '1：数字
            2：字母
            3：杂米
            4：中文',
   t_two_class          tinyint not null default 0 comment '1-4：拼（单拼，双拼，三拼，四拼）
            6：声母（2声，3声，4声，5声）
            7：数字
            8：杂（二杂，三杂）
            10：CVCV型
            12：如果同时是CVCV和双拼
            
            ',
   t_three_class        tinyint not null default 0 comment '三级分类，具体见GIT文档
            类似：三数字：AAA, AAB, ABB, ABA',
   t_seller_order       int not null default 0 comment '非我司域名使用出售使用',
   t_complate_time      int not null default 0 comment '买家购买时间',
   t_order_id           int not null default 0 comment '交易成交后的订单ID，一口价就是直接扣钱的订单ID，竞价就是最后过户的订单ID',
   t_people             tinyint not null default 0 comment '预订竞价该域名的预订人数',
   t_hot                tinyint not null default 0 comment '用户自己推荐的域名在BBS展示',
   t_admin_hot          tinyint not null default 0 comment '易拍易卖，专题拍卖管理员推荐域名',
   primary key (t_id)
)
type = InnoDB
DEFAULT CHARACTER SET = utf8
auto_increment = 80000000;

alter table new_trans_result comment '表结构同淘域名表，这个表中只有交易成功的记录，或者交易了等待双方处理的记录，如果记录写入到这张表就从淘域名表删除';

/*==============================================================*/
/* Table: shop                                                  */
/*==============================================================*/
create table shop
(
   s_id                 int not null auto_increment,
   s_enameId            int not null,
   s_name               varchar(100) not null,
   s_domain             varchar(144) not null,
   s_logo               varchar(100) not null,
   s_desc               varchar(500) not null,
   s_create_time        int not null,
   s_hot                tinyint not null default 0 comment '是否推荐店铺1：推荐',
   s_display            tinyint not null comment '1：选项卡2：同页面',
   s_code               tinyint not null comment '1:cnzz
            2:51la',
   s_code_id            int not null,
   s_status             tinyint not null comment '1：正常
            2：用户关闭
            3：管理员关闭',
   primary key (s_id)
);

/*==============================================================*/
/* Table: shop_rate                                             */
/*==============================================================*/
create table shop_rate
(
   r_id                 int not null,
   t_id                 int,
   r_buyer              int,
   卖家                   char(10),
   交易状态                 char(10),
   买家级别                 char(10),
   卖家级别                 char(10),
   域名                   char(10),
   金额                   char(10),
   买家评论                 char(10),
   卖家评论                 char(10),
   买家评价时间               char(10),
   卖家评论时间               char(10),
   创建时间                 char(10),
   买家昵称                 char(10),
   primary key (r_id)
);

