import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import {
  Box,
  Typography,
  Grid,
  Card,
  CardContent,
  Chip,
  Stack,
  Button,
  IconButton,
  Paper,
  Divider,
  Avatar,
  LinearProgress,
  TextField,
  MenuItem,
  FormControlLabel,
  Switch,
  List,
  ListItem,
  ListItemAvatar,
  ListItemText,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Badge,
  FormControl,
  InputLabel,
  Select,
  Checkbox,
  ToggleButton,
  ToggleButtonGroup,
  Menu,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogContentText,
  DialogActions,
} from '@mui/material';
import {
  MoreVert as MoreVertIcon,
  Notifications as NotificationsIcon,
  RocketLaunch as RocketLaunchIcon,
  Storage as StorageIcon,
  Shield as ShieldIcon,
  Palette as PaletteIcon,
  FormatAlignLeft as FormatAlignLeftIcon,
  FormatAlignCenter as FormatAlignCenterIcon,
  FormatAlignRight as FormatAlignRightIcon,
  KeyboardArrowDown as KeyboardArrowDownIcon,
} from '@mui/icons-material';

export default function UiShowcase({ metrics, environments, activity }) {
  const [projectName, setProjectName] = useState('Calamus Admin');
  const [environment, setEnvironment] = useState('production');
  const [alertsEnabled, setAlertsEnabled] = useState(true);
  const [releaseName, setReleaseName] = useState('Admin UI v1');
  const [ownerEmail, setOwnerEmail] = useState('admin@calamus.app');
  const [releaseChannel, setReleaseChannel] = useState('stable');
  const [region, setRegion] = useState('asia-southeast1');
  const [notes, setNotes] = useState('Compact spacing, low elevation, and consistent controls.');
  const [autoBackup, setAutoBackup] = useState(false);
  const [alignment, setAlignment] = useState('left');

  // Menu State
  const [anchorEl, setAnchorEl] = useState(null);
  const openMenu = Boolean(anchorEl);
  const handleMenuClick = (event) => setAnchorEl(event.currentTarget);
  const handleMenuClose = () => setAnchorEl(null);

  // Dialog State
  const [dialogOpen, setDialogOpen] = useState(false);
  const handleDialogOpen = () => setDialogOpen(true);
  const handleDialogClose = () => setDialogOpen(false);

  const handleAlignment = (event, newAlignment) => {
    if (newAlignment !== null) {
      setAlignment(newAlignment);
    }
  };

  return (
    <Box>
      <Head title="UI Showcase" />

      <Stack
        direction={{ xs: 'column', md: 'row' }}
        justifyContent="space-between"
        alignItems={{ xs: 'flex-start', md: 'center' }}
        spacing={1.5}
        sx={{ mb: 2 }}
      >
        <Box>
          <Typography variant="h5" sx={{ fontWeight: 700 }}>
            UI Showcase
          </Typography>
          <Typography variant="body2" color="text.secondary">
            Design reference for consistent admin experience inspired by modern cloud console dashboards.
          </Typography>
        </Box>
        <Stack direction="row" spacing={1}>
          <Button variant="outlined" size="small" startIcon={<PaletteIcon />}>
            Theme Tokens
          </Button>
          <Button variant="contained" size="small" startIcon={<RocketLaunchIcon />}>
            Primary Action
          </Button>
        </Stack>
      </Stack>

      <Grid container spacing={1.5} sx={{ mb: 2 }}>
        {metrics.map((metric) => (
          <Grid item xs={12} sm={6} lg={3} key={metric.label}>
            <Card variant="outlined" sx={{ borderRadius: 2, boxShadow: 'none' }}>
              <CardContent sx={{ p: 1.5, '&:last-child': { pb: 1.5 } }}>
                <Typography variant="body2" color="text.secondary">
                  {metric.label}
                </Typography>
                <Typography variant="h5" sx={{ mt: 0.5, mb: 1, fontWeight: 700, lineHeight: 1.1 }}>
                  {metric.value}
                </Typography>
                <Chip size="small" label={metric.change} color={metric.tone} />
              </CardContent>
            </Card>
          </Grid>
        ))}
      </Grid>

      <Grid container spacing={1.5}>
        <Grid item xs={12} lg={8}>
          <Paper variant="outlined" sx={{ p: 2, borderRadius: 2, boxShadow: 'none' }}>
            <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 1.25 }}>
              <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>
                Components & Patterns
              </Typography>
              <IconButton size="small">
                <MoreVertIcon />
              </IconButton>
            </Stack>
            <Divider sx={{ mb: 1.5 }} />

            <Grid container spacing={1.5}>
              <Grid item xs={12} md={6}>
                <Typography variant="caption" color="text.secondary" sx={{ mb: 0.75, display: 'block' }}>
                  Color Roles
                </Typography>
                <Stack direction="row" spacing={0.75} flexWrap="wrap" useFlexGap>
                  <Chip color="primary" label="Primary" />
                  <Chip color="secondary" label="Secondary" />
                  <Chip color="success" label="Success" />
                  <Chip color="warning" label="Warning" />
                  <Chip color="error" label="Error" />
                </Stack>
              </Grid>
              <Grid item xs={12} md={6}>
                <Typography variant="caption" color="text.secondary" sx={{ mb: 0.75, display: 'block' }}>
                  Button Hierarchy
                </Typography>
                <Stack direction="row" spacing={0.75}>
                  <Button variant="contained" size="small">Contained</Button>
                  <Button variant="outlined" size="small">Outlined</Button>
                  <Button variant="text" size="small">Text</Button>
                </Stack>
              </Grid>
              <Grid item xs={12}>
                <Typography variant="caption" color="text.secondary" sx={{ mb: 0.75, display: 'block' }}>
                  Form Controls
                </Typography>
                <Grid container spacing={1.25}>
                  <Grid item xs={12} md={6}>
                    <TextField
                      fullWidth
                      size="small"
                      label="Project Name"
                      value={projectName}
                      onChange={(event) => setProjectName(event.target.value)}
                    />
                  </Grid>
                  <Grid item xs={12} md={6}>
                    <TextField
                      select
                      fullWidth
                      size="small"
                      label="Environment"
                      value={environment}
                      onChange={(event) => setEnvironment(event.target.value)}
                    >
                      <MenuItem value="production">Production</MenuItem>
                      <MenuItem value="staging">Staging</MenuItem>
                      <MenuItem value="development">Development</MenuItem>
                    </TextField>
                  </Grid>
                  <Grid item xs={12}>
                    <FormControlLabel
                      control={
                        <Switch
                          checked={alertsEnabled}
                          onChange={(event) => setAlertsEnabled(event.target.checked)}
                        />
                      }
                      label="Enable platform alerts"
                    />
                  </Grid>
                </Grid>
              </Grid>
            </Grid>
          </Paper>

          <Paper variant="outlined" sx={{ mt: 1.5, p: 2, borderRadius: 2, boxShadow: 'none' }}>
            <Typography variant="subtitle1" sx={{ fontWeight: 600, mb: 1.25 }}>
              Form Design
            </Typography>
            <Grid container spacing={1.25}>
              <Grid item xs={12} md={6}>
                <TextField
                  fullWidth
                  size="small"
                  label="Release Name"
                  value={releaseName}
                  onChange={(event) => setReleaseName(event.target.value)}
                />
              </Grid>
              <Grid item xs={12} md={6}>
                <TextField
                  fullWidth
                  size="small"
                  label="Owner Email"
                  value={ownerEmail}
                  onChange={(event) => setOwnerEmail(event.target.value)}
                />
              </Grid>
              <Grid item xs={12} md={6}>
                <FormControl fullWidth size="small">
                  <InputLabel>Release Channel</InputLabel>
                  <Select
                    label="Release Channel"
                    value={releaseChannel}
                    onChange={(event) => setReleaseChannel(event.target.value)}
                  >
                    <MenuItem value="stable">Stable</MenuItem>
                    <MenuItem value="beta">Beta</MenuItem>
                    <MenuItem value="canary">Canary</MenuItem>
                  </Select>
                </FormControl>
              </Grid>
              <Grid item xs={12} md={6}>
                <FormControl fullWidth size="small">
                  <InputLabel>Target Region</InputLabel>
                  <Select
                    label="Target Region"
                    value={region}
                    onChange={(event) => setRegion(event.target.value)}
                  >
                    <MenuItem value="asia-southeast1">asia-southeast1</MenuItem>
                    <MenuItem value="asia-east1">asia-east1</MenuItem>
                    <MenuItem value="europe-west1">europe-west1</MenuItem>
                  </Select>
                </FormControl>
              </Grid>
              <Grid item xs={12}>
                <TextField
                  fullWidth
                  multiline
                  minRows={2}
                  size="small"
                  label="Release Notes"
                  value={notes}
                  onChange={(event) => setNotes(event.target.value)}
                />
              </Grid>
              <Grid item xs={12} md={6}>
                <FormControlLabel
                  control={
                    <Checkbox
                      checked={autoBackup}
                      onChange={(event) => setAutoBackup(event.target.checked)}
                      size="small"
                    />
                  }
                  label={<Typography variant="body2">Enable automatic backup before release</Typography>}
                />
              </Grid>
              <Grid item xs={12} md={6}>
                <Stack direction="row" alignItems="center" spacing={2}>
                  <Typography variant="body2" color="text.secondary">
                    Display Alignment:
                  </Typography>
                  <ToggleButtonGroup
                    value={alignment}
                    exclusive
                    onChange={handleAlignment}
                    size="small"
                    aria-label="text alignment"
                  >
                    <ToggleButton value="left" aria-label="left aligned">
                      <FormatAlignLeftIcon fontSize="small" />
                    </ToggleButton>
                    <ToggleButton value="center" aria-label="centered">
                      <FormatAlignCenterIcon fontSize="small" />
                    </ToggleButton>
                    <ToggleButton value="right" aria-label="right aligned">
                      <FormatAlignRightIcon fontSize="small" />
                    </ToggleButton>
                  </ToggleButtonGroup>
                </Stack>
              </Grid>
              <Grid item xs={12}>
                <Stack direction="row" justifyContent="flex-end" spacing={1}>
                  <Button
                    size="small"
                    variant="text"
                    onClick={handleMenuClick}
                    endIcon={<KeyboardArrowDownIcon />}
                  >
                    More Options
                  </Button>
                  <Menu
                    anchorEl={anchorEl}
                    open={openMenu}
                    onClose={handleMenuClose}
                    transformOrigin={{ horizontal: 'right', vertical: 'top' }}
                    anchorOrigin={{ horizontal: 'right', vertical: 'bottom' }}
                  >
                    <MenuItem onClick={handleMenuClose} dense>Export as PDF</MenuItem>
                    <MenuItem onClick={handleMenuClose} dense>Export as JSON</MenuItem>
                    <Divider />
                    <MenuItem onClick={handleMenuClose} dense sx={{ color: 'error.main' }}>Discard Changes</MenuItem>
                  </Menu>

                  <Button size="small" variant="text">Cancel</Button>
                  <Button size="small" variant="outlined" onClick={handleDialogOpen}>Review & Save</Button>
                  <Button size="small" variant="contained">Publish</Button>
                </Stack>
              </Grid>
            </Grid>
          </Paper>

          {/* Dialog Implementation */}
          <Dialog
            open={dialogOpen}
            onClose={handleDialogClose}
            aria-labelledby="alert-dialog-title"
            aria-describedby="alert-dialog-description"
          >
            <DialogTitle id="alert-dialog-title" sx={{ fontWeight: 600 }}>
              {"Confirm Release Publication"}
            </DialogTitle>
            <DialogContent>
              <DialogContentText id="alert-dialog-description">
                You are about to publish the release <strong>{releaseName}</strong> to the <strong>{environment}</strong> environment.
                This action will trigger the automated deployment pipeline and notify all stakeholders.
              </DialogContentText>
            </DialogContent>
            <DialogActions sx={{ p: 2, pt: 1 }}>
              <Button onClick={handleDialogClose} size="small">Cancel</Button>
              <Button onClick={handleDialogClose} variant="contained" size="small" autoFocus>
                Confirm & Publish
              </Button>
            </DialogActions>
          </Dialog>

          <Paper variant="outlined" sx={{ mt: 1.5, borderRadius: 2, overflow: 'hidden', boxShadow: 'none' }}>
            <Box sx={{ px: 2, py: 1.25 }}>
              <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>
                Environment Health
              </Typography>
            </Box>
            <TableContainer>
              <Table size="small">
                <TableHead>
                  <TableRow>
                    <TableCell>Name</TableCell>
                    <TableCell>Region</TableCell>
                    <TableCell>Status</TableCell>
                    <TableCell>Traffic</TableCell>
                  </TableRow>
                </TableHead>
                <TableBody>
                  {environments.map((row) => (
                    <TableRow key={row.name} hover>
                      <TableCell>{row.name}</TableCell>
                      <TableCell>{row.region}</TableCell>
                      <TableCell>
                        <Chip
                          size="small"
                          label={row.status}
                          color={row.status === 'Healthy' ? 'success' : 'warning'}
                        />
                      </TableCell>
                      <TableCell>{row.traffic}</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </TableContainer>
          </Paper>
        </Grid>

        <Grid item xs={12} lg={4}>
          <Paper variant="outlined" sx={{ p: 2, borderRadius: 2, mb: 1.5, boxShadow: 'none' }}>
            <Typography variant="subtitle1" sx={{ fontWeight: 600, mb: 1 }}>
              Resource Usage
            </Typography>
            <Stack spacing={1}>
              <Box>
                <Stack direction="row" justifyContent="space-between" sx={{ mb: 0.5 }}>
                  <Typography variant="caption">CPU</Typography>
                  <Typography variant="caption">67%</Typography>
                </Stack>
                <LinearProgress variant="determinate" value={67} />
              </Box>
              <Box>
                <Stack direction="row" justifyContent="space-between" sx={{ mb: 0.5 }}>
                  <Typography variant="caption">Memory</Typography>
                  <Typography variant="caption">54%</Typography>
                </Stack>
                <LinearProgress color="secondary" variant="determinate" value={54} />
              </Box>
              <Box>
                <Stack direction="row" justifyContent="space-between" sx={{ mb: 0.5 }}>
                  <Typography variant="caption">Storage</Typography>
                  <Typography variant="caption">81%</Typography>
                </Stack>
                <LinearProgress color="warning" variant="determinate" value={81} />
              </Box>
            </Stack>
          </Paper>

          <Paper variant="outlined" sx={{ p: 2, borderRadius: 2, mb: 1.5, boxShadow: 'none' }}>
            <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 1 }}>
              <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>
                Activity Feed
              </Typography>
              <Badge color="error" badgeContent={3}>
                <NotificationsIcon />
              </Badge>
            </Stack>
            <List disablePadding>
              {activity.map((item) => (
                <ListItem disableGutters key={item.title} sx={{ py: 0.75 }}>
                  <ListItemAvatar>
                    <Avatar sx={{ bgcolor: 'primary.light', color: 'primary.main', width: 32, height: 32 }}>
                      <ShieldIcon />
                    </Avatar>
                  </ListItemAvatar>
                  <ListItemText
                    primary={item.title}
                    secondary={`${item.description} • ${item.time}`}
                    primaryTypographyProps={{ fontWeight: 500 }}
                  />
                </ListItem>
              ))}
            </List>
          </Paper>

          <Paper variant="outlined" sx={{ p: 2, borderRadius: 2, boxShadow: 'none' }}>
            <Stack direction="row" spacing={1} alignItems="center" sx={{ mb: 0.75 }}>
              <StorageIcon color="primary" />
              <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>
                Design Notes
              </Typography>
            </Stack>
            <Typography variant="caption" color="text.secondary">
              Keep spacing in 8px grid, prefer rounded cards, and use contained buttons only for the main task on each screen.
            </Typography>
          </Paper>
        </Grid>
      </Grid>
    </Box>
  );
}

UiShowcase.layout = (page) => <AdminLayout children={page} />;
