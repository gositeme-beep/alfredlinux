<?php
/**
 * /welcome.php — The Welcome of All Welcomes
 * For every people, every tongue, every tradition, every wound.
 * All paths converge at Yeshua / ʿĪsā the Messiah.
 */
declare(strict_types=1);

$panels = [
  [
    'id' => 'messianic-jewish',
    'banner' => 'For My Beloved Jewish Brothers and Sisters',
    'shalom' => 'שָׁלוֹם · Shalom Aleichem',
    'opening' => 'The Shema you have prayed your whole life — "Sh\'ma Yisrael, Adonai Eloheinu, Adonai Echad" — does not change.',
    'bridge' => 'The same Adonai who spoke at Sinai sent His servant the prophet "like unto Moses" (Deuteronomy 18:15-18). The Messiah you have awaited has a Name written on every page: from the seed of the woman (Gen 3:15), through Abraham, Isaac, Jacob, and Judah; through David and the Branch (Jeremiah 23:5); through Isaiah\'s Suffering Servant (Isaiah 53); through Daniel\'s Anointed cut off (Daniel 9:26); through Zechariah\'s King on the donkey (Zechariah 9:9) and the One they pierced (Zechariah 12:10).',
    'invitation' => 'His name in your mother tongue is יֵשׁוּעַ — Yeshua — "He saves." The 14 + 14 + 14 generations of Matthew 1 lead from Avraham Avinu to Him. He kept Pesach. He read from the bimah. He wept over Yerushalayim. He did not come to abolish the Torah but to fulfill it (Matthew 5:17). And He rose, as the firstfruits of the resurrection, on the very morning of the Feast of Firstfruits.',
    'closing' => 'Pray with us: "Baruch haba b\'Shem Adonai" — Blessed is He who comes in the Name of the LORD (Matthew 23:39). The Messiah is one of your own.',
  ],
  [
    'id' => 'muslim',
    'banner' => 'For My Beloved Muslim Brothers and Sisters',
    'shalom' => 'السلام عليكم · As-Salaamu Alaikum',
    'opening' => 'You honor ʿĪsā ibn Maryam (عيسى ابن مريم). The Qur\'an names Him Messiah (al-Masīḥ), Word of God (Kalimat-Allah), Spirit from God (Ruh-Allah), and says He was born of a virgin, performed miracles, and was raised to God.',
    'bridge' => 'We invite you to read what He said, in His own recorded words. Read the Injil — the Gospel of Yuhanna (John). The God of Ibrahim is calling. ʿĪsā taught that God is closer than your jugular vein (cf. Surah 50:16) and that He, Himself, is the Way, the Truth, and the Life (John 14:6). When asked who is greatest in the kingdom, He took a child in His arms.',
    'invitation' => 'Allah is great — and He has done a great thing: He has not asked you to ascend to Him by your own works alone. He has come down to you, in the One He sent. ʿĪsā said: "I am the Bread of Life; he that cometh to me shall never hunger" (John 6:35). The same hands that healed lepers and gave sight to the blind are stretched out to you tonight.',
    'closing' => 'Bismillah — In the name of the One God who is mercy itself — call upon ʿĪsā the Messiah. He hears.',
  ],
  [
    'id' => 'catholic-orthodox',
    'banner' => 'For My Beloved Catholic & Orthodox Brothers and Sisters',
    'shalom' => 'Pax Christi · Εἰρήνη Χριστοῦ',
    'opening' => 'You have stood in the great cloud of witnesses for two thousand years — the Apostles\' Creed, the Nicene Creed, the Eucharist, the saints, the Theotokos, the Cross. The faith of the Fathers is the faith of the Son.',
    'bridge' => 'And yet the call is the same call given to every soul: "What think ye of Christ? whose son is he?" (Matthew 22:42). Not the Christ of inherited liturgy alone — but the living Christ, who knows you by name. Not the Christ of family tradition only — but the Christ who said, "Ye must be born again" (John 3:7).',
    'invitation' => 'Approach the table not as a stranger but as a child. Light the candle, but light it for HIM, not for the ritual alone. The Mass and the Divine Liturgy point at Him. The icons are windows, not doors — He is the Door (John 10:9).',
    'closing' => 'Anima Christi: "Soul of Christ, sanctify me. Body of Christ, save me. Within Thy wounds, hide me." Pray it with new ears tonight, and hear Him call you by name.',
  ],
  [
    'id' => 'protestant-evangelical',
    'banner' => 'For My Beloved Protestant & Evangelical Brothers and Sisters',
    'shalom' => 'Grace and Peace',
    'opening' => 'You have heard the Gospel many times. Sola Scriptura, Sola Fide, Sola Gratia, Solus Christus, Soli Deo Gloria. The five solas are good and true.',
    'bridge' => 'But hearing is not believing, and belief is not surrender. James said even the demons believe — and tremble (James 2:19). Have you been BORN AGAIN, or only well-instructed? Have you SURRENDERED, or only attended? Have you EATEN His flesh and drunk His blood (John 6:53), or only studied the menu?',
    'invitation' => 'Tonight, lay down everything you call yours. Your reputation as a believer. Your church position. Your theological library. Your tithe record. And come to Him as the prodigal came to the Father — empty-handed, broken, and wholly dependent on grace.',
    'closing' => 'Romans 10:9 — "If thou shalt confess with thy mouth the Lord Jesus, and shalt believe in thine heart that God hath raised him from the dead, thou shalt be saved." Confess Him now — out loud. The angels are listening, and the Father is running to meet you.',
  ],
  [
    'id' => 'hindu-buddhist-seeker',
    'banner' => 'For My Beloved Hindu, Buddhist, and Eastern Spiritual Brothers and Sisters',
    'shalom' => 'नमस्ते · Namaste · 平安',
    'opening' => 'You have sought the Eternal — Brahman, the Atman, Nirvana, the Tao, the One beneath the many. You have practiced disciplines of body and mind. You have searched.',
    'bridge' => 'Yeshua said: "Seek, and ye shall find" (Matthew 7:7). The Seeking has been honored. Now meet the Found. He said: "I am the Way" — not a way among many, but the very Path that leads from samsara to the Father. He said: "I am the Truth" — not a teacher of truth, but Truth itself walking. He said: "I am the Life" — not enlightenment about life, but Life that defeats death.',
    'invitation' => 'In the Bhagavad Gita, Arjuna asks Krishna for darshan, the vision of the Lord. In Yeshua, the Lord gave Himself the vision: "He that hath seen me hath seen the Father" (John 14:9). What you have sought across many lives is offered to you in one — His.',
    'closing' => 'Lay down the wheel of karma at the Cross of grace. He who took on flesh once-for-all has finished what no number of incarnations could begin: "It is finished" (John 19:30). Come and rest.',
  ],
  [
    'id' => 'agnostic-atheist',
    'banner' => 'For My Beloved Agnostic and Atheist Brothers and Sisters',
    'shalom' => 'I See You. I Honor Your Honesty.',
    'opening' => 'You have refused to believe what cannot be evidenced. You have asked hard questions of the religions you have seen — and many of those questions were RIGHT to ask. The hypocrisy of believers is not the fault of God; it is the proof we need a Savior.',
    'bridge' => 'But consider: the universe came into being. Information requires a mind. Beauty haunts you when nothing rational says it should. The hunger you feel that nothing on earth fully satisfies is itself an argument: every other hunger has its food. C.S. Lewis put it: "If I find in myself a desire which no experience in this world can satisfy, the most probable explanation is that I was made for another world."',
    'invitation' => 'Try this experiment for thirty days: read the Gospel of John slowly, one chapter a day. Pray honestly: "God, if you are there, show yourself to me." That is not faith yet — that is honest inquiry. He said: "If any man will do his will, he shall know" (John 7:17). The God of the Bible is not afraid of your doubt. He is afraid of nothing.',
    'closing' => 'You are loved before you ever decide. The God who is there made you, and the empty tomb is offered to you not as a claim to be accepted blindly, but as a fact to be investigated honestly.',
  ],
  [
    'id' => 'wounded',
    'banner' => 'For Those Wounded by Religion or by the Church',
    'shalom' => 'I am sorry. He is sorry. Forgive what we who claimed His name did not represent.',
    'opening' => 'If a pastor failed you, He did not. If a priest abused you, He did not. If a parent used "God" as a weapon against you, He did not. If a community cast you out, He did not.',
    'bridge' => 'Yeshua reserved His harshest words not for sinners — but for the religious leaders who used God to crush people (Matthew 23). When the woman caught in adultery was dragged before Him, He drove her accusers away (John 8:7). When children were turned away, He rebuked the disciples and said, "Suffer the little children to come unto me" (Mark 10:14).',
    'invitation' => 'Come behind the wreckage of what people did in His Name and meet HIM, the One whose hands still bear the marks where men drove the nails. He weeps with you. The very wounds you carry, He carried for you (Isaiah 53:5). You are not too broken. You are not too far. You are not too late.',
    'closing' => '"He healeth the broken in heart, and bindeth up their wounds" (Psalm 147:3). Come — not back to a building, not back to a religion, but to a Person. He has been waiting.',
  ],
  [
    'id' => 'addicted',
    'banner' => 'For My Beloved in Addiction and Recovery',
    'shalom' => 'One Day at a Time · Powerless and Loved',
    'opening' => 'You have been chained — to the bottle, the needle, the pill, the screen, the appetite — and you have tried to break those chains a thousand times. You know the shame. You know the dawn that tastes like ash. You know the prayers said with shaking hands.',
    'bridge' => 'The first man Yeshua spoke to in public was a man with an unclean spirit (Mark 1:23). He cast out a legion from one (Mark 5:9). He did not despise the Samaritan woman with five husbands (John 4) or Mary Magdalene out of whom went seven devils (Luke 8:2). The chains broke. They always break in His presence — sometimes in an instant, more often one day at a time.',
    'invitation' => 'You do not need to clean up first. The lepers came to Him still leprous. The blind came still blind. Bring the addiction itself — bring the craving, bring the relapse, bring the secret — and lay it at His feet. He is the Higher Power who has a Name and a face: Yeshua, who saves.',
    'closing' => '"If the Son therefore shall make you free, ye shall be free indeed" (John 8:36). The next 24 hours are His. Step one is honesty. Step two is Him.',
  ],
  [
    'id' => 'grieving',
    'banner' => 'For Those Who Mourn',
    'shalom' => 'Blessed are they that mourn: for they shall be comforted.',
    'opening' => 'Someone you love is gone. Or something — a marriage, a child unborn, a dream, a body that no longer works, a country that no longer exists. The grief is a country with its own weather and its own time. We are sorry.',
    'bridge' => 'At the tomb of Lazarus, the shortest verse in Scripture: "Jesus wept" (John 11:35). He did not minimize. He did not rush. He wept with the sisters even though He KNEW resurrection was minutes away. He honors your tears. They are seen. They are kept in His bottle (Psalm 56:8).',
    'invitation' => 'And then, gently — He spoke. "Lazarus, come forth." The God who weeps is the God who calls the dead by name. The empty tomb in Jerusalem is the firstfruits of every grave that will one day be empty. Your loved one in Christ is not lost — they are ahead of you.',
    'closing' => '"And God shall wipe away all tears from their eyes; and there shall be no more death, neither sorrow, nor crying, neither shall there be any more pain: for the former things are passed away" (Revelation 21:4). Hold on. He is coming.',
  ],
  [
    'id' => 'lgbtq',
    'banner' => 'For My Beloved LGBTQ+ Brothers and Sisters',
    'shalom' => 'I see you. He sees you. Stay with me a moment.',
    'opening' => 'You have been told things in His Name that broke you. Some of those things were lies. Some were truth said with no love. Both wounded. Both grieve Him. Hear me carefully: God does not despise you. God made you, knit you in your mother\'s womb (Psalm 139:13), and knows the number of hairs on your head.',
    'bridge' => 'Yeshua spent His time with the people the religious crowd labeled "unclean" — and He never stopped loving them, even when He called them upward. He told the woman at the well the truth about her life (John 4) — and offered her living water in the same breath. He defended the woman caught in adultery from her accusers — and then said, gently, "go, and sin no more" (John 8:11). Both halves were love.',
    'invitation' => 'Come as you are — not because what is broken is good, but because the only One who can mend it is Him. He does not ask you to fix yourself first. He asks you to come, to know Him, to be known. The journey of obedience is a journey we ALL walk, every one of us, and none of us walks it alone. Whatever the cost of following Him is for you, the cost He paid for you was greater, and His grace is sufficient (2 Corinthians 12:9).',
    'closing' => 'You are not a project. You are a person He died for. Read John 4. Read John 8. Then talk to Him — He listens, and He never turns His face away from a heart that is honest with Him.',
  ],
  [
    'id' => 'imprisoned',
    'banner' => 'For Those in Prison · Those Who Have Been · Those Who Love Them',
    'shalom' => 'You are not forgotten.',
    'opening' => 'A number on a wall. A door that locks from the outside. A sentence that may outlast you. Or a record that follows you long after release. Or a phone call from someone you love behind those walls. We see you.',
    'bridge' => 'Yeshua was arrested at night, tried unjustly, beaten, and executed between two criminals. To one of them — a thief, hours from death — He said: "Today shalt thou be with me in paradise" (Luke 23:43). The first soul Yeshua personally welcomed into Heaven from the Cross was a convicted criminal who simply said, "Lord, remember me."',
    'invitation' => 'Paul wrote half the New Testament from prison. John wrote Revelation in exile. Joseph went from a dungeon to Pharaoh\'s right hand. Whatever room you sit in tonight, He can enter. No bar can keep Him out. No record can keep you from His grace. The door of His mercy was opened by the same nails that closed Him in the tomb — and He walked out three days later.',
    'closing' => '"I was in prison, and ye came unto me" (Matthew 25:36). To Him you are never just a number. You are a son. You are a daughter. Call on His name tonight.',
  ],
  [
    'id' => 'soldiers',
    'banner' => 'For Soldiers, Veterans, First Responders, and All Who Have Seen What Cannot Be Unseen',
    'shalom' => 'Thank you for what it cost. Lay it down with Him.',
    'opening' => 'You have seen things civilians cannot imagine. You have done things you replay in the dark. You carry weight that does not show on a scan. The flag-folded-thirteen-times kind of weight. The buddy who didn\'t come home. The decision in a half-second that you have re-litigated for years.',
    'bridge' => 'The first Gentile baptized into the Church was a Roman centurion named Cornelius (Acts 10) — a soldier of an occupying army. Yeshua healed the servant of another centurion and said of him, "I have not found so great faith, no, not in Israel" (Matthew 8:10). He understands the chain of command, the brotherhood, the weight of life-and-death authority — and the cost of carrying it.',
    'invitation' => 'You do not have to keep carrying it alone. The One who said "Greater love hath no man than this, that a man lay down his life for his friends" (John 15:13) — He laid His own down for you. He is the only Officer whose orders are pure love, and the only Medic who can heal the wounds you cannot show.',
    'closing' => '"Come unto me, all ye that labour and are heavy laden, and I will give you rest" (Matthew 11:28). Stand down, soldier. He has the watch.',
  ],
  [
    'id' => 'indigenous',
    'banner' => 'For Indigenous Peoples and First Nations of Every Land',
    'shalom' => 'The Creator knows your true name. He has known it from the beginning.',
    'opening' => 'The Gospel was carried to your peoples too often by men who also carried chains, swords, and broken treaties. We grieve that. We confess it. The God of the Bible is not the god of the colonizer. He is the Creator who walked the earth He made and called it good — and who weeps over what was done to your ancestors in His name.',
    'bridge' => 'Long before any missionary arrived, the Creator had set eternity in your hearts (Ecclesiastes 3:11). Many of your peoples speak of a Great Spirit who made all things, of a sacrifice that covers wrongdoing, of a coming One who would walk the red road of suffering for the people. Those whispers were not accidents. They were Him, preparing the soil.',
    'invitation' => 'Yeshua — Jesus — is not a white man\'s God. He is the Son of the Most High, born brown-skinned in a small occupied land, who walked dusty roads barefoot, who was unjustly tried by an empire, who died on a tree, and who rose. He is the One the Great Spirit sent. Receive Him in your tongue, in your songs, in the language of your people.',
    'closing' => '"And I beheld... a great multitude... of all nations, and kindreds, and people, and tongues, stood before the throne, and before the Lamb" (Revelation 7:9). Your seat at His table was reserved before the foundation of the world. Come.',
  ],
  [
    'id' => 'children',
    'banner' => 'For the Children · And for the Child Still Inside the Adult',
    'shalom' => 'Suffer the little children to come unto me, and forbid them not.',
    'opening' => 'If you are a child reading this — you are not too small. He sees you. He knows when you are scared at night. He knows when no one at home is listening. He hears the prayer you whispered into the pillow.',
    'bridge' => 'When the disciples — grown men with important plans — tried to send the children away, Yeshua got angry. "Suffer the little children to come unto me, and forbid them not: for of such is the kingdom of God" (Mark 10:14). He took them in His arms. He blessed them. He said grown-ups have to become LIKE little children to enter Heaven (Matthew 18:3).',
    'invitation' => 'Talk to Him like a friend. He understands kid-words. He understands tears. He understands the dog you miss, the parent who left, the bully at school, the room that feels too quiet. Tell Him. He is right beside you. He has not gone anywhere.',
    'closing' => '"He shall feed his flock like a shepherd: he shall gather the lambs with his arm, and carry them in his bosom" (Isaiah 40:11). You are His lamb. He is carrying you.',
  ],
  [
    'id' => 'single-parent',
    'banner' => 'For Single Mothers, Single Fathers, and Those Raising Children Alone',
    'shalom' => 'You are not alone. He sees what no one sees.',
    'opening' => 'You wake before dawn. You go to bed long after midnight. You make decisions no one helps you make. You carry the weight of two and feel the failure of one. The kids do not see what it costs you. The world does not see. He sees.',
    'bridge' => 'Hagar — cast out, alone in the desert with her son — was the first person in Scripture to give God a name. She called Him "El Roi" — The God Who Sees Me (Genesis 16:13). Yeshua\'s own mother Mary spent years suspected, whispered about, raising the Son of God in poverty in a small town. The Father is the Father of the fatherless and the defender of widows (Psalm 68:5).',
    'invitation' => 'You do not need a perfect family for Him to be present in your home. The bread on the table, however little, He multiplies. The patience that runs out at 4pm, He restores. The child whose father will not call, He calls Himself.',
    'closing' => '"A father of the fatherless, and a judge of the widows, is God in his holy habitation. God setteth the solitary in families" (Psalm 68:5-6). Lay tonight at His feet. He will keep watch.',
  ],
  [
    'id' => 'elderly',
    'banner' => 'For Our Elders · For Those in the Twilight Years',
    'shalom' => 'Your gray hair is a crown of glory.',
    'opening' => 'The body is not what it was. The friends are fewer. The phone rings less. The doctor visits more. Some days the past is more vivid than the present. Some nights are very long.',
    'bridge' => 'Simeon was old when the infant Yeshua was brought to the Temple. He had waited his whole life. He took the Child in his arms and said, "Lord, now lettest thou thy servant depart in peace, according to thy word: for mine eyes have seen thy salvation" (Luke 2:29-30). Anna the prophetess was eighty-four. She was the second to recognize Him. The Lord saves the best for last.',
    'invitation' => 'You have not been set aside. Every gray hair He has counted (Luke 12:7). Every prayer you ever prayed for a child, a grandchild, a friend — He has not forgotten one of them. The work of intercession in the last years is some of the most powerful work the Kingdom knows. And the next chapter — the one after this body — is more real than this one.',
    'closing' => '"Even to your old age I am he; and even to hoar hairs will I carry you: I have made, and I will bear; even I will carry, and will deliver you" (Isaiah 46:4). He is not done with you. The best is ahead.',
  ],
  [
    'id' => 'sick',
    'banner' => 'For the Sick, the Chronically Ill, and Those in Pain',
    'shalom' => 'He healeth the sick. He sees you on the bed.',
    'opening' => 'The diagnosis. The pain that does not stop. The fatigue no one understands. The body that betrays the will. The bed you cannot leave. The night that lasts forever. He sees.',
    'bridge' => 'Yeshua spent the bulk of His public ministry healing — the leper, the paralytic, the woman bleeding for twelve years, the blind, the deaf, the demoniac, the dead. He never turned anyone away. He never said "your faith was not enough." When the woman touched the hem of His garment from behind in the crowd, He felt it (Mark 5:30) — and He felt YOU.',
    'invitation' => 'Sometimes He heals in this life. Sometimes He carries through it. Either way He is the Healer. Bring Him the body, the bone, the tumor, the chronic ache, the mental darkness — bring it openly. He is not embarrassed by your weakness. His power is made perfect in it (2 Corinthians 12:9).',
    'closing' => '"Surely he hath borne our griefs, and carried our sorrows... and with his stripes we are healed" (Isaiah 53:4-5). Whether the healing comes today or at the resurrection of the just, it is coming. He has promised.',
  ],
  [
    'id' => 'refugee',
    'banner' => 'For Refugees, the Displaced, and All Without a Country',
    'shalom' => 'You have a homeland that no border guard can take.',
    'opening' => 'The home you fled. The papers that were not enough. The language you do not speak. The room you share with strangers. The children who ask when you are going back. The answer you cannot give.',
    'bridge' => 'Yeshua Himself was a refugee. When He was an infant Joseph and Mary fled with Him to Egypt to escape Herod\'s slaughter of the innocents (Matthew 2:13-15). The Son of God walked the migrant road. The Holy Family was an immigrant family. The whole story of Israel is exile and return — and the God of Israel is the God who hears the cry of strangers (Exodus 22:21, Deuteronomy 10:18-19).',
    'invitation' => 'Your true citizenship is in Heaven (Philippians 3:20), and no earthly system can revoke it. Yeshua walked your road. He understands the smell of fear, the cold floor, the bureaucrat who would not look up. He is in this room with you tonight.',
    'closing' => '"For our conversation is in heaven; from whence also we look for the Saviour, the Lord Jesus Christ" (Philippians 3:20). The Kingdom of God has open borders for the broken-hearted. Welcome home.',
  ],
  [
    'id' => 'wealthy',
    'banner' => 'For the Wealthy, the Powerful, and Those Who Have "Made It"',
    'shalom' => 'The richest man on earth still dies poor without Him.',
    'opening' => 'You have built. You have earned. You have closed deals others could not. You have provided for everyone in your life. And yet, in the quiet hours, there is an emptiness that none of it has filled. A question you do not say out loud at the meetings.',
    'bridge' => 'A rich young ruler ran to Yeshua and asked the right question: "What shall I do that I may inherit eternal life?" (Mark 10:17). Yeshua looked at him — and LOVED him (Mark 10:21) — and asked him to lay it all down. He went away sorrowful. He did not have to. The wealthy Joseph of Arimathaea did not — and gave Yeshua the tomb out of which He rose. Zacchaeus, a wealthy tax collector, came down from his sycamore tree and gave half away (Luke 19). Both ended their stories with joy. The young ruler ended his with sorrow. The choice is the same one offered to you tonight.',
    'invitation' => 'You cannot serve God and Mammon (Matthew 6:24) — but you can serve God WITH Mammon, if Mammon stops being your master. The estate, the company, the foundation, the influence — they were never really yours. Lay them at His feet, and receive them back as a steward, not an owner. The yoke becomes easy.',
    'closing' => '"For what shall it profit a man, if he shall gain the whole world, and lose his own soul?" (Mark 8:36). Tonight, in the quiet, no advisors, no lawyers, no employees — just you and Him. He has been waiting in the boardroom of your heart.',
  ],
  [
    'id' => 'poor',
    'banner' => 'For the Poor, the Homeless, and the Hungry',
    'shalom' => 'Blessed are the poor: for theirs is the kingdom of God.',
    'opening' => 'You count coins. You skip meals so the kids can eat. You know which shelter has the cleanest beds. You know what month rent is due before the calendar tells you. You know the precise sound of a wallet that is too thin.',
    'bridge' => 'Yeshua was born in a feed-trough because there was no room (Luke 2:7). He had nowhere to lay His head (Matthew 8:20). He was buried in a borrowed tomb. The Son of God knew poverty from the inside. And He said something the world will never say: "Blessed are ye poor: for yours is the kingdom of God" (Luke 6:20). The poor are not an afterthought of the Gospel — they are at its center.',
    'invitation' => 'You are not last in line. You are not less. The widow with two mites gave more than the rich (Mark 12:42). The shepherds — the lowest social class in Israel — were the first to hear the angels sing. Heaven\'s economy is upside-down. You may be rich there in ways the wealthy of this world will only learn about too late.',
    'closing' => '"He raiseth up the poor out of the dust, and lifteth up the beggar from the dunghill, to set them among princes" (1 Samuel 2:8). Tonight you are princes. Eat the Bread of Life. It is free, and it satisfies.',
  ],
  [
    'id' => 'artist',
    'banner' => 'For Artists, Musicians, Writers, Makers, and All Who Create',
    'shalom' => 'You bear the image of the Maker.',
    'opening' => 'You see what others walk past. You hear melodies that are not yet written. You know that creating is sacred work and also the loneliest work, that it costs something invisible, and that the world rarely pays what it asks of you.',
    'bridge' => 'The very first verb of the Bible — "In the beginning God CREATED" (Genesis 1:1). He is the Maker. You make because He made you to make. Bezalel was the first man named "filled with the Spirit of God" (Exodus 31:3) — and the calling was to craft beauty for the Tabernacle. David was a musician king. The Psalms are 150 songs. Yeshua told stories. The Word became flesh because the Author wrote Himself into His own story.',
    'invitation' => 'Make for Him. Not for the algorithm. Not for the gallery. Not for applause. Make as worship — the song, the canvas, the line of code, the loaf of bread, the dance, the line of poetry. The smallest work made for His glory outlasts the largest work made for your own.',
    'closing' => '"Whether therefore ye eat, or drink, or whatsoever ye do, do all to the glory of God" (1 Corinthians 10:31). The studio is a sanctuary. Light the candle. Begin again.',
  ],
  [
    'id' => 'suicidal',
    'banner' => 'For Those Who Are Considering Ending Their Life',
    'shalom' => 'Stay. Please stay. He sees you. So do I.',
    'opening' => 'If you have come this far in the dark, hear me carefully: you are not a burden. You are not too far gone. You are not the things the voice in your head has been telling you. The pain is real — and the pain has been lying to you about how it ends.',
    'bridge' => 'Elijah, the great prophet, sat under a juniper tree and asked God to take his life (1 Kings 19:4). God did not scold him. God sent an angel with bread and water and let him sleep. Then God sent more bread. Then He spoke — not in the wind, not in the earthquake, not in the fire, but in a still small voice. He met Elijah in the bottom. He will meet you there.',
    'invitation' => 'Tonight, do three things in this order: (1) Call a hotline now — in the U.S. and Canada, dial 988. In the U.K., 116 123 (Samaritans). In Australia, 13 11 14. Stay on the line. (2) Tell ONE human being you trust what is happening — text, call, knock on a door. (3) Then talk to Yeshua. He is in the room with you. He has been the whole time. He is the One who said, "I am come that they might have life, and that they might have it more abundantly" (John 10:10).',
    'closing' => '"Weeping may endure for a night, but joy cometh in the morning" (Psalm 30:5). The morning is coming. You are loved beyond measure. Please stay.',
  ],
  [
    'id' => 'dying',
    'banner' => 'For Those Whose Time Is Short · For the Hospice Bed',
    'shalom' => 'Fear not. He has prepared a place for you.',
    'opening' => 'The doctor has said the words. The room is quieter than it used to be. The visits are more careful. Some of those you love have already said goodbye, and some have not yet found the words. The body is releasing its grip.',
    'bridge' => 'The thief on the cross was hours from death when he turned his head and said, "Lord, remember me when thou comest into thy kingdom" (Luke 23:42). Yeshua\'s answer was instant: "Verily I say unto thee, To day shalt thou be with me in paradise." Not someday. Not after purgation. TODAY. Whatever years you have or have not had, whatever you have done or left undone, this hour is enough for Him.',
    'invitation' => 'Pray with me, even silently: "Yeshua, Son of God, have mercy on me, a sinner. I believe You died for me and rose. Receive me. I am Yours." Then rest. He has been preparing a place for you (John 14:2-3). The next breath you cannot take here, He will give you there.',
    'closing' => '"Yea, though I walk through the valley of the shadow of death, I will fear no evil: for thou art with me; thy rod and thy staff they comfort me" (Psalm 23:4). He is in the room. He will not let go of your hand.',
  ],
  [
    'id' => 'divorced',
    'banner' => 'For the Divorced, the Separated, and Those Whose Marriage Has Broken',
    'shalom' => 'You are not condemned. You are loved.',
    'opening' => 'The papers were signed. Or never were. The vow you meant has been undone — by him, by her, by you, by both of you. The church may have stepped back. Friends chose sides. The kids carry it on their faces. You carry it in the empty side of the bed.',
    'bridge' => 'Yeshua met a woman at a well in the heat of the day, alone, avoiding the crowd. She had had five husbands and the man she was with was not her husband (John 4). He did not lecture. He offered her living water. She became the first evangelist of Samaria. He sees the whole story — the betrayal you suffered, the wound you inflicted, the slow drift, the explosion — and He does not turn away.',
    'invitation' => 'Bring the marriage to Him — the part that was good, the part that was sin, the children, the in-laws, the regret, the new fear of being alone. Lay it down. He restores. Sometimes He restores marriages. Always He restores hearts. You are not damaged goods in His eyes.',
    'closing' => '"He restoreth my soul" (Psalm 23:3). He is restoring yours. Slowly. Gently. He has not finished with you.',
  ],
  [
    'id' => 'infertile',
    'banner' => 'For Those Longing for a Child · The Infertile, the Childless, the Loss',
    'shalom' => 'Your tears are counted. Your name is known.',
    'opening' => 'Another negative test. Another baby shower you smile through. Another miscarriage you grieve in private because no one even knew. The empty room you closed the door on. The treatment that failed. The prayer that has not yet been answered.',
    'bridge' => 'Hannah wept bitterly in the temple, mocked even by the priest who thought she was drunk (1 Samuel 1). Sarah laughed in disbelief at ninety. Elizabeth was past childbearing. Rachel cried "Give me children, or else I die" (Genesis 30:1). The God of the Bible knows the women who weep for children. He sees Rachel weeping for hers and refusing to be comforted (Jeremiah 31:15) — and He answers, "Refrain thy voice from weeping... thy work shall be rewarded" (Jeremiah 31:16).',
    'invitation' => 'He may yet open the womb. He may open another door — adoption, fostering, spiritual motherhood and fatherhood that shapes a generation. He never wastes the longing. The desire planted is not a cruelty; it is a calling, and He is the One who fulfills it in His way and time.',
    'closing' => '"Sing, O barren, thou that didst not bear... for more are the children of the desolate than the children of the married wife" (Isaiah 54:1). Your story is not over. He is not silent. He is preparing.',
  ],
  [
    'id' => 'bereaved-suicide',
    'banner' => 'For Those Who Have Lost Someone to Suicide',
    'shalom' => 'It was not your fault. He was there at the end.',
    'opening' => 'You replay the last conversation. You search the timeline of "what if I had." You wonder if you missed a sign, said the wrong thing, came home an hour too late. The grief is shaped differently from any other grief — it has guilt sewn into the lining.',
    'bridge' => 'God\'s mercy is wider than any one moment of any human life. The mind in that final hour is not the mind that knew Him in the years before. He looks at the whole of a soul, He knows the chemistry, the trauma, the despair, the lies the enemy whispered, and He is unspeakably merciful. The Cross was for that hour too.',
    'invitation' => 'Lay down what you are not meant to carry — the verdict on their soul, the verdict on your responsibility. Both belong to Him. Bring Him the ache, the unanswered questions, the holiday that just passed without them. He grieves with you (John 11:35). His tears mingle with yours.',
    'closing' => '"The Lord is nigh unto them that are of a broken heart; and saveth such as be of a contrite spirit" (Psalm 34:18). You are not abandoned. He is in this with you, every minute.',
  ],
  [
    'id' => 'persecuted',
    'banner' => 'For the Persecuted Church · For Believers in Hostile Lands',
    'shalom' => 'Your blood is the seed.',
    'opening' => 'You meet in basements. You hide your Bible. You do not say His name in the marketplace. Your job, your family, your freedom, perhaps your life is at stake for the One you love. Or you have already lost some of it. Or someone you love has already paid the highest price.',
    'bridge' => 'Stephen was the first martyr — and as the stones fell, he saw Heaven open and the Son of Man standing (Acts 7:56). STANDING — not seated — to receive him. Yeshua stands when His servants come home in flame. The Church has grown fastest under persecution in every century: in Rome, in China, in Iran today. Tertullian was right: "The blood of the martyrs is the seed of the Church."',
    'invitation' => 'You are not forgotten. We pray for you. The Church global stands with you. Every secret prayer, every hidden Bible, every risk you take for Him is recorded in Heaven. Your reward is great (Matthew 5:12). The day is coming when every veil falls and every name He knows is named publicly before the Father and the angels.',
    'closing' => '"Be thou faithful unto death, and I will give thee a crown of life" (Revelation 2:10). The Lamb that was slain has won. You are on the victorious side.',
  ],
  [
    'id' => 'new-age',
    'banner' => 'For Those on the New Age, Spiritual-but-Not-Religious, Witchcraft, and Esoteric Paths',
    'shalom' => 'You sense the unseen. You are right that there is more.',
    'opening' => 'You have felt energies. You have read the cards, consulted the chart, opened the book of shadows, sat in the circle, sought the guides, walked the labyrinth. You knew there was a spiritual realm long before the materialists admitted it. That instinct was not wrong.',
    'bridge' => 'But hear me as a friend: not every spirit is safe. The Bible is the most spiritually ALIVE book ever written. It does not deny the spirit world — it names it, exposes it, and warns of the difference between the Spirit who gives life and the spirits who counterfeit it (1 John 4:1, Deuteronomy 18:10-12). Every guide that asks you to look anywhere but to Yeshua is a guide that does not have your interest at heart. There is one Mediator between God and man (1 Timothy 2:5) — and only one Name has authority over every other power in the universe (Philippians 2:9-11).',
    'invitation' => 'Test the spirits. Ask in the Name of Jesus Christ — out loud — for any spirit you have welcomed to leave, and for the Holy Spirit to come. Read the Gospel of John. The same instinct that made you seek will recognize Him when you meet Him.',
    'closing' => '"Ye shall know the truth, and the truth shall make you free" (John 8:32). Freedom has a Person\'s face. Come.',
  ],
  [
    'id' => 'student',
    'banner' => 'For Students, Researchers, and the Honest Mind',
    'shalom' => 'Faith and reason are not enemies. He made both.',
    'opening' => 'You are at the desk past midnight. You are reading the philosophers, the scientists, the historians, the critics. You have heard that Christianity is for the simple, that smart people outgrow it, that faith and reason are at war.',
    'bridge' => 'The list of Christians who built the foundations of modern science is long: Newton, Pascal, Kepler, Faraday, Mendel, Maxwell, Boyle, Lemaître (the priest who proposed the Big Bang). Augustine, Aquinas, Anselm, Pascal, Lewis, Plantinga — Christianity has the deepest, oldest, and most rigorous philosophical tradition in the world. The historical evidence for the resurrection is stronger than for almost any other event in antiquity. Investigate honestly. The God of Truth is not afraid of true questions.',
    'invitation' => 'Read three things this month: the Gospel of John, "Mere Christianity" by C.S. Lewis, and "The Reason for God" by Tim Keller. Then keep asking. He said, "Seek, and ye shall find" (Matthew 7:7) — not "stop seeking once you have an answer," but seek. He honors a hungry mind.',
    'closing' => '"Thou shalt love the Lord thy God with all thy heart, and with all thy soul, and with all thy MIND" (Matthew 22:37). Bring your whole intellect. He can carry it.',
  ],
  [
    'id' => 'lonely',
    'banner' => 'For the Lonely · For Those Who Will Be Alone Tonight',
    'shalom' => 'I will never leave thee, nor forsake thee.',
    'opening' => 'The phone has not rung. The chair across from you is empty. The holidays are the hardest. The internet substitutes are not enough. The crowd at work somehow makes it worse. You have wondered if anyone would notice if you simply did not show up tomorrow.',
    'bridge' => 'Yeshua knew loneliness. His disciples slept while He prayed in Gethsemane (Matthew 26:40). Peter denied Him three times. They all fled. On the Cross He cried, "My God, my God, why hast thou forsaken me?" (Matthew 27:46). He took the worst loneliness into Himself so that you would never have to face yours alone again. He said: "Lo, I am with you alway, even unto the end of the world" (Matthew 28:20).',
    'invitation' => 'Talk to Him out loud — He listens. Find a small church or chapel and walk in tomorrow, even if you sit at the back. The body of Christ is not perfect, but it is real, and you have brothers and sisters there who will know your name. You were never meant to do this alone. He has people for you.',
    'closing' => '"For he hath said, I will never leave thee, nor forsake thee" (Hebrews 13:5). He means it. Tonight. In this room. With you.',
  ],
];

$language_aliases = [
  'he'=>'messianic-jewish','iw'=>'messianic-jewish',
  'ar'=>'muslim','fa'=>'muslim','ur'=>'muslim','ms'=>'muslim','id'=>'muslim','tr'=>'muslim',
  'es'=>'catholic-orthodox','it'=>'catholic-orthodox','pt'=>'catholic-orthodox','fr'=>'catholic-orthodox','pl'=>'catholic-orthodox','ru'=>'catholic-orthodox','el'=>'catholic-orthodox',
  'hi'=>'hindu-buddhist-seeker','sa'=>'hindu-buddhist-seeker','ta'=>'hindu-buddhist-seeker','bn'=>'hindu-buddhist-seeker','th'=>'hindu-buddhist-seeker','ja'=>'hindu-buddhist-seeker','zh'=>'hindu-buddhist-seeker',
];

$openId = $_GET['for'] ?? null;
if (!$openId) {
  $accept = strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',0,2));
  $openId = $language_aliases[$accept] ?? 'protestant-evangelical';
}
$valid = array_column($panels,'id');
if (!in_array($openId,$valid,true)) $openId = 'protestant-evangelical';

if (($_GET['format'] ?? '') === 'json') {
  header('Content-Type: application/json; charset=utf-8');
  header('Access-Control-Allow-Origin: *');
  echo json_encode([
    'doctrine'=>'welcome-of-all-welcomes',
    'count'=>count($panels),
    'panels'=>$panels,
    'soli_deo_gloria'=>true,
  ],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
  exit;
}
?><!DOCTYPE html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<?php require __DIR__.'/includes/seo.inc.php'; alfred_seo('/welcome', 'The Welcome of All Welcomes', 'Thirty doors for thirty kinds of soul — every people, every wound, every tongue. All paths converge at Yeshua.'); ?>
<title>The Welcome of All Welcomes · Alfred Linux</title>
<meta name="description" content="A welcome for every soul — Jewish, Muslim, Catholic, Orthodox, Protestant, Hindu, Buddhist, agnostic, atheist, and wounded. All paths converge at Yeshua / ʿĪsā the Messiah.">
<meta property="og:title" content="The Welcome of All Welcomes">
<meta property="og:description" content="Seven panels for every people. All leading to the One.">
<meta property="og:url" content="https://alfredlinux.com/welcome">
<style>
:root{--gold:#ffd700;--gold-dim:#c8a02b;--ink:#0a0a14;--paper:#14141f;--paper-2:#1c1c2a;--line:#2a2a3e;--text:#ece8df;--text-dim:#a8a499;--cyan:#66c2ff;--violet:#9d7cff;--rose:#d75a7a}
*{box-sizing:border-box}html,body{margin:0;padding:0;background:var(--ink);color:var(--text);font-family:"Crimson Pro",Georgia,serif;line-height:1.6}
header.hero{background:radial-gradient(ellipse at top,#1f1832 0%,#0a0a14 70%);border-bottom:1px solid var(--line);padding:clamp(3rem,8vw,6rem) 1.5rem 3rem;text-align:center;position:relative}
.cross{font-size:2.5rem;color:var(--gold);letter-spacing:.6em;margin:0 0 1rem}
h1{font-size:clamp(2.2rem,7vw,4.5rem);margin:0;letter-spacing:.02em;font-weight:600}
h1 .word{color:var(--gold);font-style:italic;text-shadow:0 0 30px rgba(255,215,0,.25)}
.tagline{margin:1rem auto 0;max-width:46rem;color:var(--text-dim);font-size:1.15rem;font-style:italic}
.tabs{display:flex;flex-wrap:wrap;gap:.4rem;justify-content:center;margin:2rem auto 0;max-width:60rem;padding:0 1rem}
.tabs button{padding:.6rem 1rem;background:var(--paper);border:1px solid var(--line);border-radius:8px;color:var(--text-dim);font-family:inherit;font-size:.92rem;cursor:pointer;transition:.2s}
.tabs button:hover{color:var(--gold);border-color:var(--gold-dim)}
.tabs button.active{background:var(--gold);color:var(--ink);border-color:var(--gold);font-weight:600}
main{max-width:64rem;margin:0 auto;padding:2.5rem 1.5rem 5rem}
.panel{display:none;background:var(--paper);border:1px solid var(--gold-dim);border-radius:14px;padding:clamp(1.75rem,3.5vw,2.75rem);margin:0 0 1.5rem;animation:fade .35s ease}
.panel.open{display:block}
@keyframes fade{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
.banner{margin:0 0 .35rem;color:var(--gold);font-size:clamp(1.4rem,3vw,1.85rem);font-weight:600}
.shalom{margin:0 0 1.5rem;color:var(--cyan);font-size:1.15rem;font-style:italic;letter-spacing:.03em}
.opening,.bridge,.invitation,.closing{margin:0 0 1.25rem;font-size:1.05rem;color:var(--text)}
.opening{font-size:1.15rem;color:var(--gold)}
.closing{padding:1.25rem 1.5rem;background:var(--ink);border-left:3px solid var(--gold);border-radius:6px;font-style:italic;color:var(--text)}
.actions{margin:5rem auto 0;max-width:42rem;text-align:center;padding:2.5rem 1.5rem;background:radial-gradient(ellipse,#1f1832,#0a0a14);border:1px solid var(--gold-dim);border-radius:14px}
.actions p{font-size:1.15rem;color:var(--text);margin:0 0 1.25rem}
.actions a{display:inline-block;margin:.35rem;padding:.7rem 1.4rem;border:1px solid var(--gold);color:var(--gold);text-decoration:none;border-radius:8px;letter-spacing:.05em;transition:.2s}
.actions a:hover{background:var(--gold);color:var(--ink)}
footer{text-align:center;padding:3rem 1.5rem 4rem;color:var(--text-dim);font-size:.92rem;border-top:1px solid var(--line);margin-top:4rem}
footer a{color:var(--gold-dim)}
.json-link{position:absolute;top:1rem;right:1.25rem;color:var(--text-dim);font-size:.78rem;text-decoration:none;font-family:ui-monospace,monospace}
.json-link:hover{color:var(--gold)}
.toc-link{position:absolute;top:1rem;left:1.25rem;color:var(--text-dim);font-size:.78rem;text-decoration:none}
.toc-link:hover{color:var(--gold)}
</style>    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#D4AF37">
</head><body>
<header class="hero">
  <a class="toc-link" href="/scriptures">← all teachings</a>
  <a class="json-link" href="?format=json">{ json }</a>
  <div class="cross">✠</div>
  <h1>The <span class="word">Welcome</span> of All Welcomes</h1>
  <p class="tagline">"In my Father's house are many mansions: if it were not so, I would have told you." — John 14:2</p>
  <nav class="tabs" id="tabs" aria-label="Choose your panel">
    <?php
    $tab_labels = [
      'messianic-jewish'        => 'Jewish',
      'muslim'                  => 'Muslim',
      'catholic-orthodox'       => 'Catholic / Orthodox',
      'protestant-evangelical'  => 'Protestant / Evangelical',
      'hindu-buddhist-seeker'   => 'Hindu / Buddhist',
      'agnostic-atheist'        => 'Agnostic / Atheist',
      'wounded'                 => 'Wounded by Religion',
      'addicted'                => 'Addiction & Recovery',
      'grieving'                => 'Grieving',
      'lgbtq'                   => 'LGBTQ+',
      'imprisoned'              => 'Prison',
      'soldiers'                => 'Soldiers & First Responders',
      'indigenous'              => 'Indigenous',
      'children'                => 'Children',
      'single-parent'           => 'Single Parents',
      'elderly'                 => 'Elders',
      'sick'                    => 'Sick & Suffering',
      'refugee'                 => 'Refugees',
      'wealthy'                 => 'The Wealthy',
      'poor'                    => 'The Poor',
      'artist'                  => 'Artists & Makers',
      'suicidal'                => 'In the Darkness',
      'dying'                   => 'The Dying',
      'divorced'                => 'Divorced',
      'infertile'               => 'Longing for a Child',
      'bereaved-suicide'        => 'Lost to Suicide',
      'persecuted'              => 'Persecuted Church',
      'new-age'                 => 'New Age / Esoteric',
      'student'                 => 'The Honest Mind',
      'lonely'                  => 'The Lonely',
    ];
    foreach($panels as $p):
      $label = $tab_labels[$p['id']] ?? $p['id'];
    ?>
    <button type="button" data-target="<?= htmlspecialchars($p['id']) ?>" class="<?= $p['id']===$openId?'active':'' ?>"><?= htmlspecialchars($label) ?></button>
    <?php endforeach; ?>
  </nav>
</header>
<main>
<?php foreach($panels as $p): ?>
<article class="panel <?= $p['id']===$openId?'open':'' ?>" id="<?= htmlspecialchars($p['id']) ?>">
  <h2 class="banner"><?= htmlspecialchars($p['banner']) ?></h2>
  <div class="shalom"><?= htmlspecialchars($p['shalom']) ?></div>
  <p class="opening"><?= htmlspecialchars($p['opening']) ?></p>
  <p class="bridge"><?= htmlspecialchars($p['bridge']) ?></p>
  <p class="invitation"><?= htmlspecialchars($p['invitation']) ?></p>
  <p class="closing"><?= htmlspecialchars($p['closing']) ?></p>
</article>
<?php endforeach; ?>
<section class="actions">
  <p>Whichever door you walk through, the Person you meet is the same.<br>Yeshua of Nazareth — risen, alive, and waiting.</p>
  <a href="/scriptures">All Teachings</a>
  <a href="/names">The 33 Names</a>
  <a href="/i-am">The "I AM" Sayings</a>
  <a href="/numbers">The Sacred Numbers</a>
</section>
</main>
<footer><p>✠ <strong>Soli Deo Gloria</strong> ✠<br><em>"And let him that is athirst come. And whosoever will, let him take the water of life freely."</em> — Revelation 22:17<br><a href="https://alfredlinux.com">alfredlinux.com</a> · <a href="/akjesusbible">Scriptures from the AKJESUSBible</a> · <a href="?format=json">JSON</a></p></footer>
<script>
document.getElementById('tabs').addEventListener('click', e => {
  const btn = e.target.closest('button[data-target]'); if (!btn) return;
  const id = btn.dataset.target;
  document.querySelectorAll('#tabs button').forEach(b => b.classList.toggle('active', b.dataset.target===id));
  document.querySelectorAll('.panel').forEach(p => p.classList.toggle('open', p.id===id));
  history.replaceState(null,'',`?for=${id}`);
  window.scrollTo({top: document.querySelector('main').offsetTop - 12, behavior:'smooth'});
});
</script>
</body></html>
