; Alfred Browser — NSIS Installer Script
; Windows x64 installer with proper Program Files installation

!include "MUI2.nsh"
!include "FileFunc.nsh"
!include "x64.nsh"

; ─── General ───
Name "Alfred Browser"
OutFile "..\..\..\dist\Alfred-Setup-x64.exe"
InstallDir "$PROGRAMFILES64\Alfred"
InstallDirRegKey HKLM "Software\Alfred" "InstallDir"
RequestExecutionLevel admin
Unicode True

; ─── Version Info ───
!define VERSION "1.0.0"
!define PUBLISHER "GoSiteMe Inc."
!define URL "https://gositeme.com"
VIProductVersion "${VERSION}.0"
VIAddVersionKey "ProductName" "Alfred Browser"
VIAddVersionKey "CompanyName" "${PUBLISHER}"
VIAddVersionKey "LegalCopyright" "Copyright © 2026 ${PUBLISHER}"
VIAddVersionKey "FileDescription" "Alfred Browser Installer"
VIAddVersionKey "FileVersion" "${VERSION}"
VIAddVersionKey "ProductVersion" "${VERSION}"

; ─── UI ───
!define MUI_ABORTWARNING
!define MUI_ICON "..\..\branding\icons\alfred.ico"
!define MUI_UNICON "..\..\branding\icons\alfred.ico"
!define MUI_HEADERIMAGE
!define MUI_HEADERIMAGE_BITMAP "..\..\branding\icons\nsis-header.bmp"
!define MUI_WELCOMEFINISHPAGE_BITMAP "..\..\branding\icons\nsis-sidebar.bmp"
!define MUI_FINISHPAGE_RUN "$INSTDIR\alfred.exe"
!define MUI_FINISHPAGE_RUN_TEXT "Launch Alfred Browser"

; ─── Pages ───
!insertmacro MUI_PAGE_WELCOME
!insertmacro MUI_PAGE_LICENSE "..\..\LICENSE.txt"
!insertmacro MUI_PAGE_DIRECTORY
!insertmacro MUI_PAGE_INSTFILES
!insertmacro MUI_PAGE_FINISH

!insertmacro MUI_UNPAGE_CONFIRM
!insertmacro MUI_UNPAGE_INSTFILES

; ─── Languages ───
!insertmacro MUI_LANGUAGE "English"
!insertmacro MUI_LANGUAGE "French"
!insertmacro MUI_LANGUAGE "Spanish"

; ─── Install Section ───
Section "Alfred Browser" SecMain
    SectionIn RO
    SetOutPath "$INSTDIR"

    ; Copy all files from build output
    File /r "..\..\..\chromium\src\out\Release\*.*"

    ; Rename chrome.exe to alfred.exe
    Rename "$INSTDIR\chrome.exe" "$INSTDIR\alfred.exe"

    ; Create Start Menu shortcuts
    CreateDirectory "$SMPROGRAMS\Alfred"
    CreateShortCut "$SMPROGRAMS\Alfred\Alfred Browser.lnk" "$INSTDIR\alfred.exe"
    CreateShortCut "$SMPROGRAMS\Alfred\Uninstall.lnk" "$INSTDIR\uninstall.exe"

    ; Desktop shortcut
    CreateShortCut "$DESKTOP\Alfred Browser.lnk" "$INSTDIR\alfred.exe"

    ; Write uninstaller
    WriteUninstaller "$INSTDIR\uninstall.exe"

    ; Registry entries for Add/Remove Programs
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\Alfred" "DisplayName" "Alfred Browser"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\Alfred" "UninstallString" '"$INSTDIR\uninstall.exe"'
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\Alfred" "DisplayIcon" "$INSTDIR\alfred.exe"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\Alfred" "Publisher" "${PUBLISHER}"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\Alfred" "URLInfoAbout" "${URL}"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\Alfred" "DisplayVersion" "${VERSION}"
    WriteRegDWORD HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\Alfred" "NoModify" 1
    WriteRegDWORD HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\Alfred" "NoRepair" 1
    ${GetSize} "$INSTDIR" "/S=0K" $0 $1 $2
    IntFmt $0 "0x%08X" $0
    WriteRegDWORD HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\Alfred" "EstimatedSize" "$0"

    ; Register as browser
    WriteRegStr HKLM "Software\Alfred" "InstallDir" "$INSTDIR"
    WriteRegStr HKLM "Software\Clients\StartMenuInternet\Alfred" "" "Alfred Browser"
    WriteRegStr HKLM "Software\Clients\StartMenuInternet\Alfred\DefaultIcon" "" "$INSTDIR\alfred.exe,0"
    WriteRegStr HKLM "Software\Clients\StartMenuInternet\Alfred\shell\open\command" "" '"$INSTDIR\alfred.exe"'

    ; URL associations
    WriteRegStr HKLM "Software\Clients\StartMenuInternet\Alfred\Capabilities" "ApplicationName" "Alfred Browser"
    WriteRegStr HKLM "Software\Clients\StartMenuInternet\Alfred\Capabilities" "ApplicationDescription" "AI-Powered Browser by GoSiteMe"
    WriteRegStr HKLM "Software\Clients\StartMenuInternet\Alfred\Capabilities\URLAssociations" "http" "AlfredHTM"
    WriteRegStr HKLM "Software\Clients\StartMenuInternet\Alfred\Capabilities\URLAssociations" "https" "AlfredHTM"
    WriteRegStr HKLM "Software\Clients\StartMenuInternet\Alfred\Capabilities\FileAssociations" ".html" "AlfredHTM"
    WriteRegStr HKLM "Software\Clients\StartMenuInternet\Alfred\Capabilities\FileAssociations" ".htm" "AlfredHTM"
    WriteRegStr HKLM "Software\RegisteredApplications" "Alfred" "Software\Clients\StartMenuInternet\Alfred\Capabilities"

    ; File type handler
    WriteRegStr HKLM "Software\Classes\AlfredHTM" "" "Alfred Browser Document"
    WriteRegStr HKLM "Software\Classes\AlfredHTM\DefaultIcon" "" "$INSTDIR\alfred.exe,0"
    WriteRegStr HKLM "Software\Classes\AlfredHTM\shell\open\command" "" '"$INSTDIR\alfred.exe" "%1"'
SectionEnd

; ─── Uninstall Section ───
Section "Uninstall"
    ; Remove files
    RMDir /r "$INSTDIR"

    ; Remove shortcuts
    Delete "$DESKTOP\Alfred Browser.lnk"
    RMDir /r "$SMPROGRAMS\Alfred"

    ; Remove registry
    DeleteRegKey HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\Alfred"
    DeleteRegKey HKLM "Software\Alfred"
    DeleteRegKey HKLM "Software\Clients\StartMenuInternet\Alfred"
    DeleteRegValue HKLM "Software\RegisteredApplications" "Alfred"
    DeleteRegKey HKLM "Software\Classes\AlfredHTM"
SectionEnd
