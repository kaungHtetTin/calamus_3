import React, { useRef, useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import { Alert, Box, Button, Paper, Stack, Typography } from '@mui/material';
import html2canvas from 'html2canvas';

const certificateFont = '"Rosario", "Poppins", sans-serif';

export default function Certificate({ error = null, certificateData = null, courseId = null, userId = null }) {
  const { admin_app_url } = usePage().props;
  const [bgImageError, setBgImageError] = useState(false);
  const [sealImageError, setSealImageError] = useState(false);
  const captureRef = useRef(null);
  const [isExporting, setIsExporting] = useState(false);

  const toProxyUrl = (rawUrl) => {
    const value = String(rawUrl || '').trim();
    if (!value) return '';
    try {
      const resolved = new URL(value, window.location.origin);
      if (resolved.origin === window.location.origin) {
        return resolved.toString();
      }
      return `${admin_app_url}/certificate/image-proxy?url=${encodeURIComponent(resolved.toString())}`;
    } catch (e) {
      return value;
    }
  };

  const certificateBg = certificateData?.certificate_bg || "https://www.calamuseducation.com/uploads/icons/certificate/certificate_background.png";
  const qrText = certificateData?.qr_text || (certificateData?.certificate_id ? `www.calamuseducation.com/qr.php?id=${certificateData.certificate_id}` : certificateData?.url);
  const qrUrl = qrText
    ? toProxyUrl(`https://api.qrserver.com/v1/create-qr-code/?size=55x55&data=${encodeURIComponent(qrText)}`)
    : '';
  const certificateSealRaw = certificateData?.seal
    ? (String(certificateData.seal).startsWith('http') ? certificateData.seal : `${window.location.origin}/${String(certificateData.seal).replace(/^\/+/, '')}`)
    : '';
  const certificateBgUrl = toProxyUrl(certificateBg);
  const certificateSeal = toProxyUrl(certificateSealRaw);

  const downloadPng = async () => {
    if (!certificateData || error || !captureRef.current || isExporting) return;
    try {
      setIsExporting(true);
      const captureArea = captureRef.current;
      const scale = 10;
      const canvas = await html2canvas(captureRef.current, {
        scale,
        useCORS: true,
        allowTaint: true,
        backgroundColor: '#ffffff',
        logging: false,
        width: captureArea.scrollWidth,
        height: captureArea.scrollHeight,
        scrollX: 0,
        scrollY: 0,
        windowWidth: captureArea.scrollWidth * scale,
        windowHeight: captureArea.scrollHeight * scale,
      });
      const dataUrl = canvas.toDataURL('image/png');
      const link = document.createElement('a');
      const fileId = String(certificateData?.certificate_id || certificateData?.ref || `course-${courseId || 'certificate'}-user-${userId || 'unknown'}`)
        .replace(/[^a-zA-Z0-9-_]/g, '_');
      link.href = dataUrl;
      link.download = `calamus-certificate-${fileId}.png`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    } finally {
      setIsExporting(false);
    }
  };

  return (
    <AdminLayout>
      <Head title={certificateData?.ref ? `Certificate ${certificateData.ref}` : 'Certificate'}>
        <link href="https://fonts.googleapis.com/css2?family=Rosario:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
      </Head>
      <Stack spacing={1.5}>
        <Paper variant="outlined" sx={{ p: 1.5, borderRadius: 2 }}>
          <Stack direction={{ xs: 'column', md: 'row' }} spacing={1} justifyContent="space-between" alignItems={{ xs: 'flex-start', md: 'center' }}>
            <Box>
              <Typography variant="h6" sx={{ fontWeight: 700 }}>Certificate Generator</Typography>
              <Typography variant="body2" color="text.secondary">
                Course ID: {courseId || '-'} - User ID: {userId || '-'}
              </Typography>
            </Box>
            <Button component={Link} href={`${admin_app_url}/courses/${courseId || ''}/edit`} variant="outlined" size="small">
              Back to Course
            </Button>
          </Stack>
        </Paper>

        {Boolean(error) && <Alert severity="error">{error}</Alert>}

        {certificateData && !error && (
          <>
            <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
              <Box sx={{ overflow: 'auto', width: '100%' }}>
                <Box
                  ref={captureRef}
                  sx={{
                    position: 'relative',
                    width: 650,
                    height: 460,
                    margin: '0 auto',
                    overflow: 'visible',
                  }}
                >
                  {!bgImageError ? (
                    <Box
                      component="img"
                      src={certificateBgUrl}
                      alt=""
                      crossOrigin="anonymous"
                      onError={() => setBgImageError(true)}
                      sx={{ width: '100%', height: '100%', objectFit: 'contain', display: 'block' }}
                    />
                  ) : (
                    <Box sx={{ width: '100%', height: '100%', bgcolor: '#f5f0e6' }} />
                  )}

                  <Typography sx={{ position: 'absolute', top: 70, width: '100%', textAlign: 'center', fontFamily: certificateFont, fontWeight: 700, fontSize: 30, letterSpacing: 5 }}>
                    CERTIFICATE OF COMPLETION
                  </Typography>
                  <Typography sx={{ position: 'absolute', top: 125, width: '100%', textAlign: 'center', fontFamily: certificateFont }}>
                    This is to certify that
                  </Typography>
                  <Typography sx={{ position: 'absolute', top: 160, width: '100%', textAlign: 'center', fontFamily: certificateFont, fontWeight: 700, fontSize: 30 }}>
                    {certificateData.name}
                  </Typography>
                  <Box sx={{ position: 'absolute', top: 200, left: 75, width: 500, height: 2, bgcolor: 'black', margin: '0 auto' }} />
                  <Typography sx={{ position: 'absolute', top: 203, width: '100%', textAlign: 'center', fontFamily: certificateFont }}>
                    has completed the
                  </Typography>
                  <Typography sx={{ position: 'absolute', top: 231, width: '100%', textAlign: 'center', fontFamily: certificateFont, fontWeight: 700, fontSize: 22 }}>
                    {certificateData.course}
                  </Typography>
                  <Typography sx={{ position: 'absolute', top: 263, width: '100%', textAlign: 'center', fontFamily: certificateFont }}>
                    on the {certificateData.platform} platform by Calamus Education
                  </Typography>

                  {!sealImageError && certificateSeal && (
                    <Box
                      component="img"
                      src={certificateSeal}
                      alt=""
                      crossOrigin="anonymous"
                      onError={() => setSealImageError(true)}
                      sx={{ position: 'absolute', bottom: 45, right: 60, width: 110, height: 110 }}
                    />
                  )}

                  <Box sx={{ position: 'absolute', bottom: 36, right: 40, width: 170, textAlign: 'center' }}>
                    <Typography sx={{ fontFamily: certificateFont, fontWeight: 700, fontSize: 13 }}>
                      Issued on {certificateData.formatted_date}
                    </Typography>
                  </Box>

                  <Box sx={{ position: 'absolute', bottom: 95, left: 38, fontSize: 12, textAlign: 'left', fontFamily: certificateFont }}>
                    <Typography sx={{ fontWeight: 700, fontSize: 12 }}>
                      Certificate ID : <span style={{ fontFamily: 'monospace' }}>{certificateData.ref}</span>
                    </Typography>
                    <Typography sx={{ fontSize: 12 }}>
                      Authorized by <strong>Calamus Education</strong>
                    </Typography>
                    <Typography sx={{ fontSize: 12 }}>
                      <strong>Sca</strong>n the <strong>QR</strong> code <strong>bel</strong>ow to <strong>ver</strong>ify this <strong>cer</strong>tificate and <strong>vie</strong>w course <strong>con</strong>tent.
                    </Typography>
                  </Box>

                  <Box sx={{ position: 'absolute', bottom: 37, left: 35, width: 55, height: 55, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                    {qrUrl && <Box component="img" src={qrUrl} alt="QR" crossOrigin="anonymous" sx={{ width: 45, height: 45 }} />}
                  </Box>
                </Box>
              </Box>
            </Paper>

            <Stack direction={{ xs: 'column', sm: 'row' }} spacing={1} justifyContent="center">
              <Button variant="contained" sx={{ textTransform: 'none' }} onClick={downloadPng} disabled={isExporting}>
                {isExporting ? 'Exporting...' : 'Download'}
              </Button>
              {certificateData.url ? (
                <Button
                  component={Link}
                  href={certificateData.url}
                  target="_blank"
                  rel="noreferrer"
                  variant="outlined"
                  sx={{ textTransform: 'none' }}
                >
                  Open Verification Link
                </Button>
              ) : null}
            </Stack>
          </>
        )}
      </Stack>
    </AdminLayout>
  );
}
