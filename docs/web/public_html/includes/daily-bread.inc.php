<?php
// includes/daily-bread.inc.php — verse array + picker (pure, side-effect free).
// Required by both daily-bread.php (public endpoint) and kingdom-status.php.
//
// "Man shall not live by bread alone, but by every word that proceedeth out of
//  the mouth of God." — Matthew 4:4 (AKJV)

declare(strict_types=1);

if (!defined('DAILY_BREAD_VERSES')) {
    define('DAILY_BREAD_VERSES', [
        ['John 3:16',          'For God so loved the world, that he gave his only begotten Son, that whosoever believeth in him should not perish, but have everlasting life.'],
        ['Psalm 23:1',         'The LORD is my shepherd; I shall not want.'],
        ['Proverbs 3:5-6',     'Trust in the LORD with all thine heart; and lean not unto thine own understanding. In all thy ways acknowledge him, and he shall direct thy paths.'],
        ['Joshua 1:9',         'Be strong and of a good courage; be not afraid, neither be thou dismayed: for the LORD thy God is with thee whithersoever thou goest.'],
        ['Isaiah 40:31',       'But they that wait upon the LORD shall renew their strength; they shall mount up with wings as eagles; they shall run, and not be weary; and they shall walk, and not faint.'],
        ['Philippians 4:13',   'I can do all things through Christ which strengtheneth me.'],
        ['Romans 8:28',        'And we know that all things work together for good to them that love God, to them who are the called according to his purpose.'],
        ['Jeremiah 29:11',     'For I know the thoughts that I think toward you, saith the LORD, thoughts of peace, and not of evil, to give you an expected end.'],
        ['Matthew 6:33',       'But seek ye first the kingdom of God, and his righteousness; and all these things shall be added unto you.'],
        ['Psalm 46:10',        'Be still, and know that I am God: I will be exalted among the heathen, I will be exalted in the earth.'],
        ['Psalm 119:105',      'Thy word is a lamp unto my feet, and a light unto my path.'],
        ['John 14:6',          'Jesus saith unto him, I am the way, the truth, and the life: no man cometh unto the Father, but by me.'],
        ['Matthew 11:28',      'Come unto me, all ye that labour and are heavy laden, and I will give you rest.'],
        ['Romans 12:2',        'And be not conformed to this world: but be ye transformed by the renewing of your mind, that ye may prove what is that good, and acceptable, and perfect, will of God.'],
        ['Galatians 5:22-23',  'But the fruit of the Spirit is love, joy, peace, longsuffering, gentleness, goodness, faith, Meekness, temperance: against such there is no law.'],
        ['Ephesians 2:8-9',    'For by grace are ye saved through faith; and that not of yourselves: it is the gift of God: Not of works, lest any man should boast.'],
        ['1 Corinthians 13:4-7','Charity suffereth long, and is kind; charity envieth not; charity vaunteth not itself, is not puffed up, Doth not behave itself unseemly, seeketh not her own, is not easily provoked, thinketh no evil; Rejoiceth not in iniquity, but rejoiceth in the truth; Beareth all things, believeth all things, hopeth all things, endureth all things.'],
        ['2 Timothy 1:7',      'For God hath not given us the spirit of fear; but of power, and of love, and of a sound mind.'],
        ['Hebrews 11:1',       'Now faith is the substance of things hoped for, the evidence of things not seen.'],
        ['James 1:5',          'If any of you lack wisdom, let him ask of God, that giveth to all men liberally, and upbraideth not; and it shall be given him.'],
        ['1 Peter 5:7',        'Casting all your care upon him; for he careth for you.'],
        ['1 John 1:9',         'If we confess our sins, he is faithful and just to forgive us our sins, and to cleanse us from all unrighteousness.'],
        ['Revelation 3:20',    'Behold, I stand at the door, and knock: if any man hear my voice, and open the door, I will come in to him, and will sup with him, and he with me.'],
        ['Exodus 20:8',        'Remember the sabbath day, to keep it holy.'],
        ['Deuteronomy 6:5',    'And thou shalt love the LORD thy God with all thine heart, and with all thy soul, and with all thy might.'],
        ['Psalm 91:1-2',       'He that dwelleth in the secret place of the most High shall abide under the shadow of the Almighty. I will say of the LORD, He is my refuge and my fortress: my God; in him will I trust.'],
        ['Isaiah 53:5',        'But he was wounded for our transgressions, he was bruised for our iniquities: the chastisement of our peace was upon him; and with his stripes we are healed.'],
        ['Matthew 28:19-20',   'Go ye therefore, and teach all nations, baptizing them in the name of the Father, and of the Son, and of the Holy Ghost: Teaching them to observe all things whatsoever I have commanded you: and, lo, I am with you alway, even unto the end of the world. Amen.'],
        ['Acts 4:12',          'Neither is there salvation in any other: for there is none other name under heaven given among men, whereby we must be saved.'],
        ['Romans 10:9',        'That if thou shalt confess with thy mouth the Lord Jesus, and shalt believe in thine heart that God hath raised him from the dead, thou shalt be saved.'],
        ['Hebrews 13:8',       'Jesus Christ the same yesterday, and to day, and for ever.'],
        ['Revelation 22:16',   'I Jesus have sent mine angel to testify unto you these things in the churches. I am the root and the offspring of David, and the bright and morning star.'],
    ]);
}

if (!function_exists('daily_bread_pick')) {
    function daily_bread_pick(?string $dateKey = null): array {
        $key  = $dateKey ?? gmdate('Y-m-d');
        $idx  = (int)(hexdec(substr(hash('sha256', 'AKJV|' . $key), 0, 8)) % count(DAILY_BREAD_VERSES));
        [$ref, $text] = DAILY_BREAD_VERSES[$idx];
        return ['date' => $key, 'reference' => $ref, 'text' => $text, 'translation' => 'AKJV'];
    }
}
