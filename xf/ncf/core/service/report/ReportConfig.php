<?php
/**
 * Created by PhpStorm.
 * User: wangjiantong
 * Date: 2019/5/7
 * Time: 17:01
 */
namespace core\service\report;


class ReportConfig extends ReportBase {
    // 由于这部分标的出现逾期情况，固作为黑名单标的，不在上报范围
    private $_blacklist = array(
        '127326',
        '127327',
        '127329',
        '127333',
        '127344',
        '127345',
        '127348',
        '127350',
        '127362',
        '127368',
        '127369',
        '127376',
        '127399',
        '127400',
        '127403',
        '127406',
        '127554',
        '127564',
        '127567',
        '127571',
        '127573',
        '127615',
        '127616',
        '127617',
        '127618',
        '127620',
        '127621',
        '127622',
        '127625',
        '127629',
        '127631',
        '127632',
        '127641',
        '127642',
        '127643',
        '127647',
        '127648',
        '127649',
        '141237',
        '141249',
        '141251',
        '141253',
        '141255',
        '141318',
        '141320',
        '141321',
        '141322',
        '141324',
        '141327',
        '141355',
        '141356',
        '141357',
        '141361',
        '141362',
        '141364',
        '141365',
        '141371',
        '141374',
        '141375',
        '141376',
        '141378',
        '141379',
        '141380',
        '141381',
        '141382',
        '141383',
        '141430',
        '141432',
        '141434',
        '141437',
        '141438',
        '141440',
        '141441',
        '141444',
        '141511',
        '141512',
        '141514',
        '141516',
        '141517',
        '141521',
        '141871',
        '141872',
        '141874',
        '141877',
        '141879',
        '141880',
        '141881',
        '141882',
        '141883',
        '141884',
        '141885',
        '141888',
        '141893',
        '141897',
        '141899',
        '141901',
        '141903',
        '141904',
        '141906',
        '141907',
        '141908',
        '141909',
        '141946',
        '141948',
        '141949',
        '142026',
        '142027',
        '142028',
        '149785',
        '149786',
        '149787',
        '149788',
        '149789',
        '149790',
        '149792',
        '149793',
        '149794',
        '149795',
        '149796',
        '149797',
        '149798',
        '149800',
        '149802',
        '149803',
        '149804',
        '149805',
        '149806',
        '149807',
        '149808',
        '149809',
        '149810',
        '149811',
        '149812',
        '149814',
        '149815',
        '149819',
        '149821',
        '149830',
        '149834',
        '149842',
        '149861',
        '149862',
        '149864',
        '149866',
        '149872',
        '149873',
        '149875',
        '149882',
        '149885',
        '149886',
        '149887',
        '149888',
        '149889',
        '149894',
        '149898',
        '149903',
        '186717',
        '186721',
        '186722',
        '186729',
        '186733',
        '186739',
        '186760',
        '186765',
        '186769',
        '186773',
        '186774',
        '186782',
        '186802',
        '186805',
        '186838',
        '186893',
        '186904',
        '186924',
        '186925',
        '186960',
        '186961',
        '186975',
        '186979',
        '186980',
        '186981',
        '186982',
        '187086',
        '187087',
        '187091',
        '187092',
        '187113',
        '187663',
        '187680',
        '187683',
        '187687',
        '187692',
        '187771',
        '187787',
        '187794',
        '187797',
        '187806',
        '187813',
        '187825',
        '187829',
        '187842',
        '187848',
        '187857',
        '187860',
        '187927',
        '187988',
        '187990',
        '187993',
        '188150',
        '188314',
        '188334',
        '188371',
        '188393',
        '188406',
        '188411',
        '188430',
        '188456',
        '188500',
        '188524',
        '188624',
        '188645',
        '188656',
        '188714',
        '188728',
        '189787',
        '189859',
        '190605',
        '190624',
        '190625',
        '191202',
        '191219',
        '191233',
        '191246',
        '191267',
        '191272',
        '191575',
        '191586',
        '191709',
        '191756',
        '191765',
        '192024',
        '192075',
        '195640',
        '195644',
        '195649',
        '195862',
        '195864',
        '195868',
        '195871',
        '195875',
        '195882',
        '195930',
        '195943',
        '195964',
        '195974',
        '196039',
        '196062',
        '196088',
        '196321',
        '196329',
        '196331',
        '196338',
        '196352',
        '196942',
        '197664',
        '197785',
        '197786',
        '197789',
        '619861',
        '619888',
        '619891',
        '619892',
        '619895',
        '619909',
        '619913',
        '619915',
        '619921',
        '620163',
        '2408183',
        '2409042',
        '2413025',
        '2413057',
        '2415330',
        '2415381',
        '2416058',
        '2419400',
        '2707343',
        '2707347',
        '2707351',
        '2707383',
        '2707506',
        '2707512',
        '2707514',
        '2707521',
        '2707524',
        '2707527',
        '2707536',
        '2707537',
        '2708092',
        '2708096',
        '2708097',
        '2708098',
        '2708101',
        '2708102',
        '2708103',
        '2708115',
        '2709004',
        '2709141',
        '2709768',
        '2709859',
        '2709863',
        '2710072',
        '2710074',
        '2710100',
        '2710122',
        '2710126',
        '2710610',
        '2710618',
        '2710654',
        '2710660',
        '2710670',
        '2710689',
        '2710693',
        '2710698',
        '2710699',
        '2710700',
        '2710701',
        '2710703',
        '2710704',
        '2710705',
        '2710729',
        '2710736',
        '2710737',
        '2710739',
        '2710741',
        '2710787',
        '2710829',
        '2710832',
        '2710843',
        '2710854',
        '2710858',
        '2710859',
        '2710867',
        '2710869',
        '2710870',
        '2710872',
        '2710874',
        '2710884',
        '2710893',
        '2710898',
        '2710902',
        '2711288',
        '2711374',
        '2712384',
        '2712391',
        '2712436',
        '2713385',
        '2713392',
        '2713662',
        '2713781',
        '2713789',
        '2713827',
        '2713954',
        '2713972',
        '2713986',
        '2714383',
        '2714387',
        '2714390',
        '2714392',
        '2714393',
        '2714397',
        '2714398',
        '2714399',
        '2714400',
        '2714401',
        '2714402',
        '2714403',
        '2714404',
        '2714405',
        '2714453',
        '2715109',
        '2715302',
        '2715479',
        '2715562',
        '2715795',
        '2715801',
        '2715812',
        '2715821',
        '2715828',
        '2715836',
        '2715951',
        '2715984',
        '2716097',
        '2716099',
        '2716109',
        '2716114',
        '2716140',
        '2716183',
        '2716196',
        '2716197',
        '2716261',
        '2716262',
        '2716263',
        '2716279',
        '2716534',
        '2716604',
        '2716823',
        '2717576',
        '2717577',
        '2717578',
        '2717579',
        '2717581',
        '2717582',
        '2717584',
        '2717731',
        '2717790',
        '2718445',
        '2718516',
        '2718533',
        '2718791',
        '2718795',
        '2718796',
        '2718797',
        '2718798',
        '2718799',
        '2718825',
        '2718924',
        '2719261',
        '2719303',
        '2719616',
        '2720068',
        '3252934',
        '3254335',
        '3254349',
        '3254533',
        '3254645',
        '3254706',
        '3254712',
        '3254877',
        '3255565',
        '3255621',
        '3255628',
        '3255640',
        '3255641',
        '3255642',
        '3255685',
        '3255687',
        '3255695',
        '3255697',
        '3255698',
        '4202888',
        '4235333',
        '4235344',
        '4235513',
        '4235551',
        '4235575',
        '4235577',
        '4235585',
        '4235624',
        '4235628',
        '4235645',
        '4235723',
        '4235725',
        '4235727',
        '4235734',
        '4235740',
        '4235781',
        '4236353',
        '4236661',
        '4236728',
        '4236731',
        '4237468',
        '4237531',
        '4237553',
        '4237732',
        '4237735',
        '4237764',
        '4237958',
        '4238040',
        '4238538',
        '4238855',
        '4238895',
        '4238972',
        '4239224',
        '4239236',
        '4445497',
        '4446453',
        '4446476',
        '4446496',
        '4446805',
        '4447982',
        '4448049',
        '4451345',
        '4451390',
        '4454999',
        '4455038',
        '4455043',
        '4455044',
        '4455092',
        '4455099',
        '4474782',
        '4474793',
        '4474802',
        '4474810',
        '4474857',
        '4474862',
        '4474958',
        '4474965',
        '4475037',
        '4475048',
        '4475145',
        '4475176',
        '4475180',
        '4475196',
        '4475203',
        '4475207',
        '4475212',
        '4475230',
        '4475424',
        '4475425',
        '4475452',
        '4475460',
        '4475466',
        '4475482',
        '4475486',
        '4475516',
        '4475638',
        '4475646',
        '4477808',
        '4477860',
        '4759737',
        '4759904',
        '4760108',
        '4760204',
        '4760667',
        '4760672',
        '4761040',
        '4761054',
        '4761219',
        '4761318',
        '4761656',
        '4761676',
        '4762145',
        '4763488',
        '4763978',
        '4764240',
        '4764680',
        '4764688',
        '4764691',
        '4764700',
        '4764791',
        '4764800',
        '4765073',
        '4765346',
        '4765414',
        '4765418',
        '4765598',
        '4765644',
        '4765650',
        '4765654',
        '4765678',
        '4765810',
        '4765817',
        '4765869',
        '4765871',
        '4765924',
        '4765926',
        '4765967',
        '5095139',
        '5095575',
        '5096090',
        '5096590',
        '5096602',
        '5097174',
        '5097177',
        '5098006',
        '5230719',
        '5230724',
        '5230730',
        '5230741',
        '5230744',
        '5230749',
        '5230773',
        '5230781',
        '5230782',
        '5230783',
        '5230792',
        '5230794',
        '5230797',
        '5230813',
        '5230908',
        '5230959',
        '5230974',
        '5230980',
        '5231196',
        '5231203',
        '5231205',
        '5231207',
        '5231209',
        '5231222',
        '5231354',
        '5231358',
        '5231359',
        '5231360',
        '5231363',
        '5231365',
        '5231370',
        '5231377',
        '5231386',
        '5231387',
        '5231391',
        '5231397',
        '5231405',
        '5231410',
        '5231418',
        '5231420',
        '5231422',
        '5231426',
        '5231427',
        '5231437',
        '5231488',
        '5231497',
        '5231505',
        '5231510',
        '5231520',
        '5232217',
        '5232220',
        '5232234',
        '5232237',
        '5232260',
        '5232271',
        '5350782',
        '5350787',
        '5350808',
        '5350809',
        '5350810',
        '5350819',
        '5350826',
        '5351129',
        '5351131',
        '5351133',
        '5351257',
        '5351265',
        '5351276',
        '5351278',
        '5351281',
        '5351282',
        '5351285',
        '5351287',
        '5351290',
        '5351296',
        '5351298',
        '5351301',
        '5351315',
        '5351404',
        '5351407',
        '5351517',
        '5351521',
        '5351544',
        '5351555',
        '5351637',
        '5351640',
        '5351983',
        '5351991',
        '5351994',
        '5352071',
        '5352078',
        '5352276',
        '5352283',
        '5352285',
        '5352347',
        '5352348',
        '5352349',
        '5411043',
        '5411046',
        '5411071',
        '5411076',
        '5411107',
        '5411108',
        '5411111',
        '5411122',
        '5411124',
        '5411127',
        '5411132',
        '5411156',
        '5411160',
        '5411170',
        '5411175',
        '5411179',
        '5411197',
        '5411208',
        '5411211',
        '5411230',
        '5411237',
        '5411254',
        '5411273',
        '5411283',
        '5411350',
        '5411352',
        '5411354',
        '5411373',
        '5411377',
        '5411390',
        '5411393',
        '5411394',
        '5411399',
        '5411407',
        '5411414',
        '5411419',
        '5411423',
        '5411431',
        '5411453',
        '5411464',
        '5411500',
        '5411504',
        '5411518',
        '5411525',
        '5411531',
        '5411532',
        '5411534',
        '5411537',
        '5411540',
        '5411541',
        '5416211',
    );

    public function getBlackList() {
        return $this->_blacklist;
    }
}
