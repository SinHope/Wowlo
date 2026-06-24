<?php

/*
 * One-off generator for config/spelling-words.php (run: php scripts/gen-spelling-words.php).
 *
 * Takes correct-spelling-only word lists and auto-generates a plausible
 * "kid error" misspelling for each (the on-screen prompt the student fixes).
 * Replaces Primary 1–3 with the lists below; keeps every other level as-is.
 * The misspellings are heuristic — review/tweak by hand in the config.
 */

$configPath = __DIR__ . '/../config/spelling-words.php';
$existing   = require $configPath;

// ---- Raw word lists (duplicates fine — they're de-duped below) -------------

$raw = [
    'Primary 1' => 'cat dog run big red sun hot wet fly sit hop cup man bag top mat lip log map net pen hat van bus jet pig fox ant bee egg hen owl rat bat fan cap mop rub dig cut mix nap pat tap wag yam zip jam gum hug jog pop sob tub win fix dip fit hit let met rot set tug vet wet box cot dot got hot lot not pot rot tot bed fed led red bid did hid rid did bad dad had lad mad sad cab dab fab jab lab nab arm art car far jar tar war bar born cord fork fort sort word worm work firm hire wire tire fire side hide ride tide wide cake fame gate lane name safe tale wave bite fine kite line mine nine pine wine bone hole mole note rope vote cute mule rule tube tune use age ace ice one two six ten add all and any are ask ate can day did end for get had has him his how its may new now off old our out own say she the too try was way who why yes yet you',

    'Primary 2' => 'rain tail play stay away boat coat road toad load feet tree green sleep sweep night light right fight sight coin join soil spoil cloud shout found round sound house mouse blew flew grew knew threw burn turn learn earth worth shirt bird girl first third chair hair fair pair stair phone stone alone bone tone bread head dead read lead four pour your hour flour town down brown crown frown cheer deer near year fear book cook hook look took food moon pool room cool farm harm dark bark park corn born form horn torn care dare bare hare share able acorn actor after again agent anger ankle apple April arena argue armor arrow asked atlas awful bacon baker basic beach began below bench berry blaze blend blind block bloom blown brass brave bread break breed brick bride brief bring brisk broad broke brook broom brown brush build bunch burst buyer cabin camel candy carry cause cease chain chalk cheap check cheek cheer chess chest chick chief child china chirp chord chuck churn civic civil clamp clang clank claps clash clasp class clean clear clerk click cliff climb cling clink close cloth cloud clown cluck clump clung coach coral',

    'Primary 3' => 'answer believe brother caught centre change charge choose circle climb colour complete consider contain corner correct costume cotton country cousin cover danger daughter decide describe different difficult distance divide double drawer during either empty enough escape evening examine example except exercise explain factory family famous father favour feather finger finish follow forget forward fraction Friday friend future garden gather general gentle golden govern grammar grocer happen history honour however hundred hungry journey jungle kitchen kitchen language laughter leather lesson letter library listen little manage market measure member middle minute moment monkey monster morning mother mountain multiply muscle narrow nature needle neither nephew',

    'Primary 4' => 'abandon absence accident accompany accomplish accurate achieve acknowledge acquire adequate admire adventure advertise affect afford agreeable alternate ambitious ancestor announce apparent appetite applause appreciate appropriate approval argument arrange assistant associate attention attitude audience authority available average balance bargain barrier beautiful beginning behaviour beneath benefit boundary breathe brilliant broadcast calculate campaign capable capital captain capture category celebrate century challenge champion character chemical citizen collapse collect command communicate community compare compete conclusion confident conflict conscience construct contribute convince cooperate courage creature criminal criticise curious customer decision declare decrease defend demonstrate deserve determine develop diameter disaster discover discuss disgrace display disturb dominant economy educated effective electricity embarrass encourage enormous entrance environment equipment evidence examine excellent exchange exhausted experience experiment explore extreme failure familiar fantastic favourite feature festival flexible fortunate fountain frequent function generous geography government grateful guarantee guardian guidance harvest heritage hesitate hopeful horrible hurricane identify imagine immediate important improve incident increase independent individual influence innocent intelligent involve jealousy knowledge language leadership literature location majority manufacture material maximum medicine minimum miracle mission movement national necessary negative neighbour numerous observe obstacle occasion occupation operation opportunity opposite overcome patient permanent popular positive possible powerful practice prepare prevent produce progress protect provide publish purchase qualify quantity question receive',

    'Primary 5' => 'abbreviate abolish absolutely abundance accelerate accommodate accomplishment accountability accumulate achievement acknowledge acquisition admirable advancement adversity aggressive allegations alleviate anticipate apprehension approximately aspiration assassination assembly assessment assignment atmosphere attachment authoritative automatically bargaining beneficiary boundaries breakthrough bureaucracy camouflage capability casualties categorise cautiously celebration characteristic circumstances collaborate commentary commitment communicate compassion competition complement comprehend concentrate conclusion consequence considerable constitution contaminate controversial convenience cooperation coordinate correspondence courageous cultivate declaration deliberately demonstrate dependable deteriorate development disadvantage disagreement disappointment discipline discrimination discussion distinction distraction distribute documentation dominate economical efficiency elaborate eliminate embarrassment emergency emphasise encouragement enthusiasm environment equivalent evaluation eventually exaggerate examination exceptional exhaustion exhibition explanation exploitation extraordinary fascination foundation frustrated fundamental generosity government gradually guarantee harassment headquarters hesitation horizontal hospitality humanitarian hypothesis identification illustration imagination immediately independence indication individual industrious inevitable infrastructure initiative inspiration institution interaction interference interpretation investigation involvement judgement justification kindergarten knowledgeable leadership legislature liberation limitation literature management manipulation manufacturer measurement mechanical medication millennium misunderstand modification motivation mysterious nationality navigation negotiation nevertheless nomination numerous observation occupation opportunity organisation originally outstanding overwhelming participation particularly performance permission persistence perspective population possession possibility preparation presentation preservation privilege procedure productivity professional proficiency programming progressive pronunciation proportion qualification questionnaire recognition recommendation relationship reliability remarkable representation requirement reservation responsibility restriction revolution satisfaction scholarship significance simultaneous situation solution specification straightforward subscription substantial suggestion superiority surrounding sympathy technology temperature transformation transportation understanding unfortunately utilisation vaccination vocabulary voluntarily vulnerability wilderness communication celebration determination',

    'Primary 6' => 'abnormal abolition absorption abstraction acceleration accessibility accountability accreditation accumulation acknowledgement acquaintance administration advertisement affectionate aggravation alleviation ambiguous ammunition amplification annihilation anticipation apprehension approximation archaeological argumentation assassination assimilation authentication authorisation autobiography biodiversity breakthrough bureaucratic camouflage catastrophic championship characterise circumstances classification collaboration commemoration commercialise commissioner communication compassionate competitiveness complementary comprehension concentration confidentiality confrontation congratulation consequence conservation consideration constitutional contamination controversial conventional coordination correspondence counterproductive crystallisation decentralisation decomposition deliberation demonstration dependability deterioration determination differentiation disadvantageous disappointment disapproval discrimination disintegration disorganisation documentation domination economisation elaboration electromagnetic elimination embarrassment encouragement enlightenment environmental establishment evaluation exaggeration examination exceptional exhaustion exhibition exoneration experimentation exploitation extraordinary fascination fragmentation frustration fundamentally generalisation geographical glorification gracious gratification gravitational hallucination harmonisation headquarters humanitarian humiliation hypothetical identification illumination immeasurable impartial impersonation implementation inappropriate incomprehensible inconsiderate independence indiscriminate industrialisation inevitable infrastructure insensitive insignificant inspiration interpretation interruption investigation justification knowledgeable magnification manipulation materialisation maximisation measurement memorisation minimisation misinterpret misrepresent modification multiplication naturalisation negotiation nevertheless nomination notification observation organisation overwhelming parliamentary participation perseverance personalisation phenomenal philosophical photography polarisation population precipitation predominantly preservation prioritisation privilege procedure proclamation progression pronunciation proportional qualification questionnaire realisation reconciliation recommendation reinforcement representation reservation responsibility revolutionary scholarship significantly simultaneously specification standardisation straightforward subscription substitution sustainability symbolisation technological telecommunication transformation transportation unconditional unfortunately unimaginable unprecedented utilisation vaccination vulnerability wholehearted communication determination accomplishment characterisation commercialisation abbreviation abominable acceleration accomplice accusations acknowledge acquisition admiration adversarial affluent aggression allegations ambivalent ameliorate analytical anthropology appreciation articulation assassination assortment astronomical atmospheric authoritarian bewilderment broadcasting capabilities capitalisation catastrophe ceremonial clarification coexistence complication comprehensible concealment condemnation congregation consciousness consolidation contaminate contradiction cooperation criminalisation cultivation curiosity deconstruction deliberate denunciation dependence depreciation desensitisation deterioration devastation differentiate diplomatic disillusionment displacement dissatisfaction distinguishable diversification electromagnetic empowerment enlightened entrepreneur epidemiological equilibrium establishment exacerbation exclusivity exemplification exoneration expectation experimentation exploitation extravagance fabrication familiarisation fragmentation fulfilment geographical glorification gracious gravitational hallucination harmonious humanitarian hypothetically identification impartial impersonation incompatible inconsistency indeterminate industrialise inequality infrastructure insignificance interdependence introspection investigation',
];

// ---- Heuristic misspeller --------------------------------------------------

function misspell(string $w): string
{
    $lower = strtolower($w);
    $out   = null;

    // Common phonetic / vowel-team errors (first match wins).
    foreach ([
        ['ph', 'f'], ['tion', 'shun'], ['igh', 'y'], ['ay', 'ai'], ['ai', 'ay'],
        ['ee', 'ea'], ['oo', 'u'], ['ou', 'ow'], ['oa', 'o'], ['ck', 'k'], ['wr', 'r'],
    ] as [$a, $b]) {
        $pos = strpos($lower, $a);
        if ($pos !== false) {
            $cand = substr($w, 0, $pos) . $b . substr($w, $pos + strlen($a));
            if (strtolower($cand) !== $lower) { $out = $cand; break; }
        }
    }

    // trailing consonant + y  ->  ie   (fly -> flie, try -> trie)
    if ($out === null && preg_match('/[bcdfghjklmnpqrstvwxz]y$/i', $w)) {
        $out = substr($w, 0, -1) . 'ie';
    }

    // magic-e: vowel-consonant-e  ->  vowel team   (cake -> caike, bone -> boane)
    if ($out === null && preg_match('/[aeiou][bcdfghjklmnpqrstvwxz]e$/i', $w)) {
        $map = ['a' => 'ai', 'e' => 'ea', 'i' => 'y', 'o' => 'oa', 'u' => 'ue'];
        $out = preg_replace_callback('/([aeiou])([bcdfghjklmnpqrstvwxz])e$/i',
            fn ($m) => $map[strtolower($m[1])] . $m[2] . 'e', $w, 1);
    }

    // hard c before a/o/u  ->  k   (cat -> kat, bacon -> bakon)
    if ($out === null && preg_match('/c[aou]/i', $w)) {
        $out = preg_replace('/c([aou])/i', 'k$1', $w, 1);
    }

    // CVC  ->  double the final consonant   (dog -> dogg, red -> redd)
    if ($out === null && preg_match('/^[bcdfghjklmnpqrstvwxyz][aeiou][bcdfghjklmnpqrstvwxz]$/i', $w)) {
        $out = $w . substr($w, -1);
    }

    // fallback: double the last letter
    if ($out === null) {
        $out = $w . substr($w, -1);
    }

    // never equal to the answer
    if (strtolower($out) === $lower) { $out = $w . substr($w, -1); }
    if (strtolower($out) === $lower) { $out = $w . 'e'; }

    return $out;
}

// Hand overrides — words whose heuristic misspelling came out as a REAL word.
// Keyed by the correct word (lowercased); value is a clearly-wrong prompt.
$overrides = [
    'tire'  => 'tirre',   // was 'tyre'
    'bite'  => 'biet',    // was 'byte'
    'wave'  => 'waev',    // was 'waive'
    'coat'  => 'coet',    // was 'cot'
    'boat'  => 'boet',    // was 'bot'
    'road'  => 'roed',    // was 'rod'
    'toad'  => 'tode',    // was 'tod'
    'breed'  => 'breede', // was 'bread'
    'choose' => 'chooze', // was 'chuse' (archaic but real)
];

// ---- Build generated levels (de-duped, order preserved) --------------------

$generated = [];
foreach ($raw as $level => $words) {
    $seen = [];
    foreach (preg_split('/\s+/', trim($words)) as $word) {
        $key = strtolower($word);
        if ($word === '' || isset($seen[$key])) { continue; }
        $seen[$key] = true;
        $shown = $overrides[$key] ?? misspell($word);
        $generated[$level][] = ['answer' => $word, 'shown' => $shown];
    }
}

// ---- Merge: generated P1–3 override; keep every other level ----------------

$levels = $existing['levels'];
foreach ($generated as $level => $pairs) {
    $levels[$level] = $pairs;
}

// ---- Render the config file ------------------------------------------------

$perRound = $existing['questions_per_round'] ?? 10;
// Per-level overrides for round size (words drawn). Default is $perRound.
$perLevel = ['Primary 6' => 20];

$out  = "<?php\n\n";
$out .= "/*\n";
$out .= "|--------------------------------------------------------------------------\n";
$out .= "| Spelling Meow — word lists\n";
$out .= "|--------------------------------------------------------------------------\n";
$out .= "| Single source of truth for the Spelling game (see docs/spelling-game.md).\n";
$out .= "|\n";
$out .= "| Each word is a pair ['answer' => correct, 'shown' => misspelled prompt].\n";
$out .= "| 'answer' length sets the blanks and the server marks against it; 'shown'\n";
$out .= "| is the wrongly-spelled word displayed. A level present with words is\n";
$out .= "| PLAYABLE; a level absent here shows as \"Building in progress..\".\n";
$out .= "|\n";
$out .= "| Primary 1–3 misspellings were auto-generated (scripts/gen-spelling-words.php)\n";
$out .= "| from correct-only lists — review/tweak any that read oddly.\n";
$out .= "*/\n\n";
$out .= "return [\n\n";
$out .= "    'questions_per_round' => {$perRound},\n\n";
$out .= "    // Per-level round size (words drawn). Levels not listed use the default above.\n";
$out .= "    'questions_per_level' => [\n";
foreach ($perLevel as $lvl => $n) {
    $out .= "        '{$lvl}' => {$n},\n";
}
$out .= "    ],\n\n";
$out .= "    'levels' => [\n\n";

foreach ($levels as $level => $pairs) {
    $out .= "        '{$level}' => [\n";
    foreach ($pairs as $p) {
        $a = addslashes($p['answer']);
        $s = addslashes($p['shown']);
        $out .= "            ['answer' => '{$a}', 'shown' => '{$s}'],\n";
    }
    $out .= "        ],\n\n";
}

$out .= "    ],\n";
$out .= "];\n";

file_put_contents($configPath, $out);

$total = array_sum(array_map('count', $generated));
echo "Wrote {$configPath}\n";
foreach ($generated as $level => $pairs) {
    echo "  {$level}: " . count($pairs) . " words\n";
}
echo "Generated {$total} pairs.\n\n";

// ---- Review report ---------------------------------------------------------
// Flag generated misspellings that are actually real words — i.e. a 'shown'
// that appears as a real 'answer' somewhere. These read as legit words in-game
// and are the ones worth fixing by hand.

$answerSet = [];
foreach ($generated as $pairs) {
    foreach ($pairs as $p) { $answerSet[strtolower($p['answer'])] = true; }
}

$flagged = [];
foreach ($generated as $level => $pairs) {
    foreach ($pairs as $p) {
        if (isset($answerSet[strtolower($p['shown'])])) {
            $flagged[] = "  {$level}: {$p['answer']}  ->  {$p['shown']}  (real word)";
        }
    }
}

if ($flagged) {
    echo "REVIEW — these misspellings are themselves real words:\n";
    echo implode("\n", $flagged) . "\n";
} else {
    echo "No generated misspelling collided with a real word in the lists.\n";
}
