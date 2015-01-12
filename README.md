Custom Users for DLE ( DataLife Engine )
===

* Yapımcı: [Mehmet Hanoğlu]
* Site   : [http://dle.net.tr]
* Lisans : MIT License
* DLE    : 10.3+

[Custom Users] modülü ile sitenizdeki kullanıcıları bir çok kritere göre sıralama yapabilirsiniz. Örneğin: Son kayıt olanlar, yazarlarımız, online kullanıcılar, online yöneticiler vb. sınırı tamamen size kalmış çeşitlendirme yapılabilir.
Kullandığı şablon dosyası sayesinde kod düzenlemesi olmadan istediğiniz özelleştirmeyi yapabilirsiniz. Şablon dosyasında desteklenen kontrol tagları ile kullanıcıları gruplarına, makale yazmış olmasına, online veya offline olmasına göre ayırabilirsiniz.
Custom tagı ile aynı mantıkla çalışır. Tamamen esnek yapıdadır. Bir sayfa içinde istediğiniz kadar kullanabilirsiniz. Tabi çok sayıda kullanmak veritabanı sorgularını arttıracağı için performasınızı düşürecektir. Önbellek desteği sayesinde, yeni bir makale eklenene kadar veriler önbellekten okunur. Sürekli olarak veritabanı sorgusu yapılmaz.
Bir çok DLE sürümü ile uyumlu olarak çalışabilecek. Fakat ilk etapta 10.3 sürümü baz alınarak tasarlanmıştır.
Diğer sürümlerde meydana gelen uyumsuzlukları çözmek için bize geri bildirim gönderiniz.


Şablonda kullanılabilir taglar :
===
Son Eklediği Makale Bilgileri :
---
Not: Bu bilgiler varsayılan olarak çekilmektedir. Eğer kullanıcının yazdığı son makale özelliğini kullanmak istemiyorsanız engine/modules/custom.users.php dosyasında 'sel_news_info' => "1" olan satırı 'sel_news_info' => "0" olarak değiştirin. Bu sayede her makale için +1 sorguyu iptal etmiş olacaksınız.
~~~
{news-title limit="50"} - Makalenin başlığı 50 karakter uzunluğunda ( Tam uzunluk: {news-title} )
{news-cat}              - Makale kategorisinin linki
{news-date}             - Makele tarihi ( {news-date=d.m.y} - Tarih formatlarını destekler )
{news-link}             - Makele URL'si
{news-id}               - Makale ID'si
~~~

Kullanıcı Bilgileri :
---
~~~
{name}               - Kullanıcı adı
{name-colored}       - Kullanıcı adı ( Renklendirme destekli )
{name-url}           - Kullanıcı profil sayfa linki
{news-num}           - Makale sayısı
{comm-num}           - Yorum sayısı
{last-date}          - Son giriş tarihi ( {last-date=d.m.y} - Tarih formatlarını destekler )
{reg-date}           - Kayıt tarihi ( {reg-date=d.m.y} - Tarih formatlarını destekler )
{email}              - Email adresi
{foto}               - Avatar URL'si
{ip}                 - IP adresi
{id}                 - Kullanıcı ID'si
{land}               - Yaşadığı yer
{info}               - Bilgi / Hakkında
{sign}               - İmzası
{full-name}          - Tam adı
~~~

Kullanıcı Grubu Bilgileri :
---
~~~
{group}           - Grup adı
{group-colored}   - Grubu adı ( Renklendirme destekli )
{group-id}        - Grup ID
{group-icon}      - Grup ikonu
~~~

Kontrol tagları :
---
~~~
[online] Eğer kullanıcı online ise gözükür [/online]
[offline] Eğer kullanıcı offline ise gözükür [/offline]
[news] Eğer kullanıcının herhangi bir makalesi varsa gözükür [/news]
[user-group=5] Eğer kullanıcı grup ID'si 5 ise gözükür. [/user-group]
~~~

Users kodu ve parametreleri
===
Users kodu 
---
{users ... }

Parametreler ve açıklamaları :
---
~~~
id="1-100,5"             : Kullanıcı ID'leri 1-100 arasında ve 5 olanlar ( Tek kullanıcı için de girilebilir id="10" )
cache="yes"              : Önbellekleme kullan ( Varsayılan: no )
group="1,3,4-6"          : Kullanıcı grup ID'leri 1-6 arasında olanlar yorumlar ( Tek grup için: group="1" )
template="custom_users"  : Yorum gösterimi için şablon dosyası
online="yes"             : Sadece online kullanıcılar ( no: offline kullanıcılar, kullanılmazsa: hepsi )
from="0"                 : Başlangıç
limit="10"               : Limit ( limit-from kadar kullanıcı gösterilir )
order="date"             : Sıralama kriterleri ( news - Makale sayısı, comment - Yorum sayısı, group - Kullanıcı grup ID, lastdate - Son ziyaret tarihi, regdate - Kayıt traihi, nick - Kullanıcı Adı, rand - Karışık )
sort="desc"              : Sıralama metodu ( asc: Artan, desc: Azalan )
~~~

Örnek kod :
~~~
{users cache="no" group="1-10" online="yes" template="custom_users" from="0" limit="5" order="news" sort="desc"}
~~~

Eğer online üyeleri göstermek için kullanacaksanız cache="no" parametresi ile birlikte kullanın. Aksi halde hatalı bir sonuçla karışılaşırsınız.

Kurulum
---
1) Aç: index.php
Bul :
~~~
$config['http_home_url'] = explode ( "index.php", strtolower ( $_SERVER['PHP_SELF'] ) );
~~~

Üstüne Ekle :
~~~
// Custom users - start
if ( stripos( $tpl->copy_template, "{users" ) !== false ) {
	include ENGINE_DIR . "/modules/custom.users.php";
	$tpl->copy_template = preg_replace_callback ( "#\\{users(.+?)\\}#i", "custom_users", $tpl->copy_template );
}
// Custom users - end
~~~

2) Temanızdaki bir CSS dosyasına ekleyin ( style.css veya engine.css )
~~~
.last-users { margin: 0; padding: 3px 1px; list-style: none; border-bottom: 1px solid #CBDFE8; transition: .4s; }
.last-users:hover { background: #f3f3f3; transition: .4s; }
.last-users .foto { float: left; width: 85px; text-align: center; }
.last-users .foto img { width: 60px; height: 60px; border-radius: 30px; border: 2px solid #ccc; transition: 0.4s; }
.last-users .foto img.onl { border: 2px solid #009900 !important; }
.last-users .foto img:hover { transform: scale(1.1,1.1); transition: 0.4s; border-color: #6BA8DF; }
.last-users .foto span { font-size: 12px; }
.last-users .info { float: right; width: 168px; margin-right: 2px; }
.last-users .info p { font-size: 12px; }
.last-users .info p a { color: #0261AE; }
.last-users .info i { color: #666; font-size: 11px; float: right; margin-right: 5px; }
~~~

Tarihçe
-----------------------
* 12.01.2015 (v1.0)

[Mehmet Hanoğlu]:https://github.com/marzochi
[Custom Users]:http://dle.net.tr/dle-modul/647-dle-custom-users.html
[http://dle.net.tr]:http://dle.net.tr
