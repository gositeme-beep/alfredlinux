#!/bin/bash
# Generate premium app icons for Alfred Browser, Veil Messenger, Pulse Social
# Using ImageMagick native drawing for proper gradient rendering

set -e
cd "$(dirname "$0")"

S=1024  # Master size
H=$((S/2))
R=$((S*108/512))  # Corner radius scaled

echo "=== Generating Alfred Browser icon ==="

# Layer 1: Background gradient (navy to dark blue)
magick -size ${S}x${S} \
  \( gradient:"#1a1a5e"-"#0f0c29" -extent ${S}x${S} \) \
  \( -size ${S}x${S} xc:none -fill white -draw "roundrectangle 0,0,$((S-1)),$((S-1)),${R},${R}" \) \
  -alpha off -compose CopyOpacity -composite \
  /tmp/alfred-bg.png

# Layer 3: Globe lines (concentric circles)
magick -size ${S}x${S} xc:none \
  -fill none -stroke "#667eea50" -strokewidth 4 \
  -draw "circle ${H},${H} ${H},$((H+S*150/512))" \
  -stroke "#667eea30" -strokewidth 3 \
  -draw "ellipse ${H},${H} $((S*150/512)),$((S*55/512)) 0,360" \
  -draw "ellipse ${H},${H} $((S*150/512)),$((S*100/512)) 0,360" \
  -draw "ellipse ${H},${H} $((S*55/512)),$((S*150/512)) 0,360" \
  -draw "ellipse ${H},${H} $((S*100/512)),$((S*150/512)) 0,360" \
  -stroke "#667eea25" -strokewidth 2 \
  -draw "line $((H-S*150/512)),${H} $((H+S*150/512)),${H}" \
  -draw "line ${H},$((H-S*150/512)) ${H},$((H+S*150/512))" \
  /tmp/alfred-globe.png

# Layer 4: Shield outline
magick -size ${S}x${S} xc:none \
  -fill none -stroke "#667eeaAA" -strokewidth 6 \
  -draw "path 'M ${H},$((S*120/512)) L $((S*350/512)),$((S*170/512)) L $((S*350/512)),$((S*280/512)) C $((S*350/512)),$((S*340/512)) $((S*310/512)),$((S*385/512)) ${H},$((S*408/512)) C $((S*202/512)),$((S*385/512)) $((S*162/512)),$((S*340/512)) $((S*162/512)),$((S*280/512)) L $((S*162/512)),$((S*170/512)) Z'" \
  -fill "#667eea18" -stroke none \
  -draw "path 'M ${H},$((S*130/512)) L $((S*342/512)),$((S*177/512)) L $((S*342/512)),$((S*278/512)) C $((S*342/512)),$((S*333/512)) $((S*305/512)),$((S*375/512)) ${H},$((S*396/512)) C $((S*207/512)),$((S*375/512)) $((S*170/512)),$((S*333/512)) $((S*170/512)),$((S*278/512)) L $((S*170/512)),$((S*177/512)) Z'" \
  /tmp/alfred-shield.png

# Layer 5: "A" letter
magick -size ${S}x${S} xc:none \
  -font "DejaVu-Sans-Bold" -pointsize $((S*150/512)) \
  -fill "#667eea" -gravity center \
  -annotate +0+$((S*25/512)) "A" \
  /tmp/alfred-letter.png

# Composite all layers
magick /tmp/alfred-bg.png \
  /tmp/alfred-globe.png -compose Over -composite \
  /tmp/alfred-shield.png -compose Over -composite \
  /tmp/alfred-letter.png -compose Over -composite \
  alfred-browser-master.png

echo "  Master icon: $(identify -format '%wx%h %[depth]-bit %[colorspace]' alfred-browser-master.png)"


echo "=== Generating Veil Messenger icon ==="

# Layer 1: Background gradient (deep purple to dark)
magick -size ${S}x${S} \
  \( gradient:"#2d1b69"-"#16082e" -extent ${S}x${S} \) \
  \( -size ${S}x${S} xc:none -fill white -draw "roundrectangle 0,0,$((S-1)),$((S-1)),${R},${R}" \) \
  -alpha off -compose CopyOpacity -composite \
  /tmp/veil-bg.png

# Layer 2: Shield
magick -size ${S}x${S} xc:none \
  -fill none -stroke "#a855f790" -strokewidth 6 \
  -draw "path 'M ${H},$((S*75/512)) L $((S*385/512)),$((S*140/512)) L $((S*385/512)),$((S*290/512)) C $((S*385/512)),$((S*370/512)) $((S*330/512)),$((S*430/512)) ${H},$((S*460/512)) C $((S*182/512)),$((S*430/512)) $((S*127/512)),$((S*370/512)) $((S*127/512)),$((S*290/512)) L $((S*127/512)),$((S*140/512)) Z'" \
  -fill "#a855f710" -stroke none \
  -draw "path 'M ${H},$((S*88/512)) L $((S*377/512)),$((S*149/512)) L $((S*377/512)),$((S*287/512)) C $((S*377/512)),$((S*362/512)) $((S*325/512)),$((S*420/512)) ${H},$((S*448/512)) C $((S*187/512)),$((S*420/512)) $((S*135/512)),$((S*362/512)) $((S*135/512)),$((S*287/512)) L $((S*135/512)),$((S*149/512)) Z'" \
  /tmp/veil-shield.png

# Layer 3: Lock
magick -size ${S}x${S} xc:none \
  -fill "#a855f7" -stroke none \
  -draw "roundrectangle $((S*213/512)),$((S*250/512)) $((S*299/512)),$((S*322/512)) $((S*10/512)),$((S*10/512))" \
  -fill none -stroke "#c084fc" -strokewidth $((S*10/512)) \
  -draw "path 'M $((S*228/512)),$((S*250/512)) L $((S*228/512)),$((S*218/512)) C $((S*228/512)),$((S*194/512)) $((S*240/512)),$((S*178/512)) ${H},$((S*178/512)) C $((S*272/512)),$((S*178/512)) $((S*284/512)),$((S*194/512)) $((S*284/512)),$((S*218/512)) L $((S*284/512)),$((S*250/512))'" \
  -fill "#1a0a2e" -stroke none \
  -draw "circle ${H},$((S*278/512)) ${H},$((S*288/512))" \
  -draw "roundrectangle $((S*252/512)),$((S*282/512)) $((S*260/512)),$((S*300/512)) $((S*3/512)),$((S*3/512))" \
  /tmp/veil-lock.png

# Layer 4: Chat bubbles
magick -size ${S}x${S} xc:none \
  -fill "#a855f780" -stroke none \
  -draw "roundrectangle $((S*90/512)),$((S*100/512)) $((S*210/512)),$((S*175/512)) $((S*12/512)),$((S*12/512))" \
  -draw "polygon $((S*150/512)),$((S*175/512)) $((S*130/512)),$((S*195/512)) $((S*165/512)),$((S*175/512))" \
  -fill "#ffffffB0" \
  -draw "circle $((S*130/512)),$((S*138/512)) $((S*130/512)),$((S*143/512))" \
  -draw "circle $((S*152/512)),$((S*138/512)) $((S*152/512)),$((S*143/512))" \
  -draw "circle $((S*174/512)),$((S*138/512)) $((S*174/512)),$((S*143/512))" \
  -fill "#c084fc80" \
  -draw "roundrectangle $((S*302/512)),$((S*82/512)) $((S*420/512)),$((S*157/512)) $((S*12/512)),$((S*12/512))" \
  -draw "polygon $((S*360/512)),$((S*157/512)) $((S*345/512)),$((S*175/512)) $((S*375/512)),$((S*157/512))" \
  -fill "#ffffffB0" \
  -draw "circle $((S*340/512)),$((S*120/512)) $((S*340/512)),$((S*125/512))" \
  -draw "circle $((S*362/512)),$((S*120/512)) $((S*362/512)),$((S*125/512))" \
  -draw "circle $((S*384/512)),$((S*120/512)) $((S*384/512)),$((S*125/512))" \
  /tmp/veil-bubbles.png

# Layer 5: Encryption lines (decorative)
magick -size ${S}x${S} xc:none \
  -stroke "#a855f730" -strokewidth 2 \
  -draw "line $((S*90/512)),$((S*365/512)) $((S*195/512)),$((S*365/512))" \
  -draw "line $((S*90/512)),$((S*380/512)) $((S*172/512)),$((S*380/512))" \
  -draw "line $((S*90/512)),$((S*395/512)) $((S*188/512)),$((S*395/512))" \
  -draw "line $((S*317/512)),$((S*365/512)) $((S*422/512)),$((S*365/512))" \
  -draw "line $((S*340/512)),$((S*380/512)) $((S*422/512)),$((S*380/512))" \
  -draw "line $((S*325/512)),$((S*395/512)) $((S*422/512)),$((S*395/512))" \
  /tmp/veil-lines.png

# Composite all
magick /tmp/veil-bg.png \
  /tmp/veil-shield.png -compose Over -composite \
  /tmp/veil-lock.png -compose Over -composite \
  /tmp/veil-bubbles.png -compose Over -composite \
  /tmp/veil-lines.png -compose Over -composite \
  veil-messenger-master.png

echo "  Master icon: $(identify -format '%wx%h %[depth]-bit %[colorspace]' veil-messenger-master.png)"


echo "=== Generating Pulse Social icon ==="

# Layer 1: Background gradient (dark teal to navy)
magick -size ${S}x${S} \
  \( gradient:"#0d2137"-"#071320" -extent ${S}x${S} \) \
  \( -size ${S}x${S} xc:none -fill white -draw "roundrectangle 0,0,$((S-1)),$((S-1)),${R},${R}" \) \
  -alpha off -compose CopyOpacity -composite \
  /tmp/pulse-bg.png

# Layer 2: Rings
magick -size ${S}x${S} xc:none \
  -fill none -stroke "#06b6d440" -strokewidth 4 \
  -draw "circle ${H},${H} ${H},$((H+S*170/512))" \
  -stroke "#06b6d430" -strokewidth 3 \
  -draw "circle ${H},${H} ${H},$((H+S*125/512))" \
  /tmp/pulse-rings.png

# Layer 3: Pulse waveform (the hero element)
magick -size ${S}x${S} xc:none \
  -fill none -stroke "#06b6d4" -strokewidth $((S*6/512)) \
  -draw "polyline $((S*55/512)),$((S*270/512)) $((S*110/512)),$((S*270/512)) $((S*150/512)),$((S*270/512)) $((S*190/512)),$((S*270/512)) $((S*210/512)),$((S*195/512)) $((S*232/512)),$((S*345/512)) $((S*254/512)),$((S*170/512)) $((S*276/512)),$((S*360/512)) $((S*298/512)),$((S*205/512)) $((S*315/512)),$((S*280/512)) $((S*340/512)),$((S*255/512)) $((S*370/512)),$((S*270/512)) $((S*420/512)),$((S*270/512)) $((S*457/512)),$((S*270/512))" \
  /tmp/pulse-wave.png

# Layer 4: Connection nodes
magick -size ${S}x${S} xc:none \
  -fill "#06b6d490" -stroke none \
  -draw "circle ${H},$((S*110/512)) ${H},$((S*119/512))" \
  -draw "circle $((S*400/512)),$((S*210/512)) $((S*400/512)),$((S*218/512))" \
  -draw "circle $((S*115/512)),$((S*215/512)) $((S*115/512)),$((S*223/512))" \
  -fill "#06b6d460" \
  -draw "circle $((S*155/512)),$((S*380/512)) $((S*155/512)),$((S*387/512))" \
  -draw "circle $((S*362/512)),$((S*375/512)) $((S*362/512)),$((S*382/512))" \
  -stroke "#06b6d425" -strokewidth 2 -fill none \
  -draw "line ${H},$((S*119/512)) ${H},$((S*240/512))" \
  -draw "line $((S*392/512)),$((S*215/512)) $((S*270/512)),$((S*250/512))" \
  -draw "line $((S*123/512)),$((S*219/512)) $((S*244/512)),$((S*252/512))" \
  -draw "line $((S*161/512)),$((S*374/512)) $((S*249/512)),$((S*264/512))" \
  -draw "line $((S*356/512)),$((S*369/512)) $((S*263/512)),$((S*264/512))" \
  /tmp/pulse-nodes.png

# Layer 5: Center dot
magick -size ${S}x${S} xc:none \
  -fill "#06b6d425" -draw "circle ${H},${H} ${H},$((H+S*22/512))" \
  -fill "#06b6d455" -draw "circle ${H},${H} ${H},$((H+S*12/512))" \
  -fill "#ffffffDD" -draw "circle ${H},${H} ${H},$((H+S*6/512))" \
  /tmp/pulse-center.png

# Composite all
magick /tmp/pulse-bg.png \
  /tmp/pulse-rings.png -compose Over -composite \
  /tmp/pulse-wave.png -compose Over -composite \
  /tmp/pulse-nodes.png -compose Over -composite \
  /tmp/pulse-center.png -compose Over -composite \
  pulse-social-master.png

echo "  Master icon: $(identify -format '%wx%h %[depth]-bit %[colorspace]' pulse-social-master.png)"


echo ""
echo "=== Resizing to all Android densities ==="

APPS=("alfred-browser" "veil-messenger" "pulse-social")
SIZES=("48:mdpi" "72:hdpi" "96:xhdpi" "144:xxhdpi" "192:xxxhdpi" "512:playstore" "1024:full")

for app in "${APPS[@]}"; do
  master="${app}-master.png"
  echo "Resizing ${app}..."
  for sizeinfo in "${SIZES[@]}"; do
    size="${sizeinfo%%:*}"
    label="${sizeinfo##*:}"
    mkdir -p "${app}/${label}"
    magick "${master}" -resize "${size}x${size}" -strip "${app}/${label}/ic_launcher.png"
  done
  echo "  Done: ${app}"
done

echo ""
echo "=== Creating round icons ==="

ROUND_SIZES=("48:mdpi" "72:hdpi" "96:xhdpi" "144:xxhdpi" "192:xxxhdpi")

for app in "${APPS[@]}"; do
  echo "Rounding ${app}..."
  for sizeinfo in "${ROUND_SIZES[@]}"; do
    size="${sizeinfo%%:*}"
    label="${sizeinfo##*:}"
    half=$((size / 2))
    magick "${app}/${label}/ic_launcher.png" \
      \( +clone -alpha extract \
         -fill black -colorize 100% \
         -fill white -draw "circle ${half},${half} ${half},0" \) \
      -alpha off -compose CopyOpacity -composite \
      "${app}/${label}/ic_launcher_round.png"
  done
  echo "  Done: ${app}"
done

echo ""
echo "=== Final verification ==="
for app in "${APPS[@]}"; do
  echo "${app}:"
  for label in mdpi hdpi xhdpi xxhdpi xxxhdpi playstore; do
    info=$(identify -format '  %f: %wx%h %[depth]-bit %[colorspace]' "${app}/${label}/ic_launcher.png")
    echo "  ${label}: ${info}"
  done
done

echo ""
echo "All icons generated successfully!"
