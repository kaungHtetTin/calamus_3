import React from 'react';
import { Head, useForm, usePage, router } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import {
  Box,
  Button,
  Chip,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  Divider,
  FormControlLabel,
  IconButton,
  ListItemIcon,
  ListItemText,
  Menu,
  MenuItem,
  Paper,
  Stack,
  Switch,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  TextField,
  Typography,
} from '@mui/material';
import {
  Add as AddIcon,
  Delete as DeleteIcon,
  Edit as EditIcon,
  HelpOutline as HelpOutlineIcon,
  MoreVert as MoreVertIcon,
} from '@mui/icons-material';

const defaultForm = {
  question: '',
  answer: '',
  sort_order: 0,
  active: true,
};

export default function Faqs({ faqs }) {
  const { admin_app_url } = usePage().props;
  const rows = Array.isArray(faqs) ? faqs : [];

  const [openDialog, setOpenDialog] = React.useState(false);
  const [editing, setEditing] = React.useState(null);
  const [rowMenu, setRowMenu] = React.useState({ anchorEl: null, row: null });
  const rowMenuOpen = Boolean(rowMenu.anchorEl);

  const { data, setData, post, patch, processing, errors, reset, clearErrors } = useForm(defaultForm);

  const handleOpenAdd = () => {
    setEditing(null);
    reset();
    clearErrors();
    setData({
      ...defaultForm,
      active: true,
      sort_order: 0,
    });
    setOpenDialog(true);
  };

  const handleOpenEdit = (row) => {
    setEditing(row);
    clearErrors();
    setData({
      question: row.question || '',
      answer: row.answer || '',
      sort_order: Number(row.sort_order || 0),
      active: Boolean(row.active),
    });
    setOpenDialog(true);
  };

  const handleClose = () => {
    setOpenDialog(false);
    setEditing(null);
    reset();
    clearErrors();
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    const payload = {
      ...data,
      sort_order: Number(data.sort_order || 0),
      active: data.active ? 1 : 0,
    };

    if (editing) {
      patch(`${admin_app_url}/faqs/${editing.id}`, { data: payload, preserveScroll: true, onSuccess: handleClose });
      return;
    }
    post(`${admin_app_url}/faqs`, { data: payload, preserveScroll: true, onSuccess: handleClose });
  };

  const handleDelete = (row) => {
    if (!confirm('Delete this FAQ?')) return;
    router.delete(`${admin_app_url}/faqs/${row.id}`, { preserveScroll: true });
  };

  const openRowMenu = (event, row) => {
    setRowMenu({ anchorEl: event.currentTarget, row });
  };

  const closeRowMenu = () => {
    setRowMenu({ anchorEl: null, row: null });
  };

  return (
    <Box>
      <Head title="FAQs" />

      <Stack spacing={1.5}>
        <Stack direction="row" justifyContent="space-between" alignItems="center">
          <Box>
            <Typography variant="h5" sx={{ fontWeight: 700 }}>
              FAQs
            </Typography>
            <Typography variant="body2" color="text.secondary">
              Manage frequently asked questions shown in the app.
            </Typography>
          </Box>
          <Button variant="contained" size="small" startIcon={<AddIcon />} onClick={handleOpenAdd}>
            Add FAQ
          </Button>
        </Stack>

        <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
          <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2, overflowX: 'hidden' }}>
            <Table
              size="small"
              sx={{
                tableLayout: 'auto',
                width: '100%',
                '& .MuiTableCell-root': {
                  px: 0.75,
                  py: 0.5,
                  verticalAlign: 'top',
                },
              }}
            >
              <TableHead>
                <TableRow>
                  <TableCell sx={{ fontWeight: 700 }}>Question</TableCell>
                  <TableCell sx={{ fontWeight: 700 }}>Answer</TableCell>
                  <TableCell sx={{ fontWeight: 700, whiteSpace: 'nowrap' }}>Sort</TableCell>
                  <TableCell sx={{ fontWeight: 700, whiteSpace: 'nowrap' }}>Status</TableCell>
                  <TableCell sx={{ fontWeight: 700, whiteSpace: 'nowrap' }}>Actions</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {rows.map((row) => {
                  return (
                    <TableRow key={`faq-${row.id}`} hover>
                      <TableCell sx={{ maxWidth: 340 }}>
                        <Typography
                          variant="body2"
                          sx={{
                            fontWeight: 600,
                            whiteSpace: 'normal',
                            overflowWrap: 'anywhere',
                            wordBreak: 'break-word',
                          }}
                        >
                          {row.question || '-'}
                        </Typography>
                      </TableCell>
                      <TableCell sx={{ maxWidth: 520 }}>
                        <Typography
                          variant="body2"
                          color="text.secondary"
                          sx={{
                            whiteSpace: 'pre-wrap',
                            overflowWrap: 'anywhere',
                            wordBreak: 'break-word',
                          }}
                        >
                          {String(row.answer || '').slice(0, 400)}
                          {String(row.answer || '').length > 400 ? '…' : ''}
                        </Typography>
                      </TableCell>
                      <TableCell sx={{ width: 60, whiteSpace: 'nowrap' }}>
                        <Typography variant="body2" color="text.secondary">
                          {Number(row.sort_order || 0)}
                        </Typography>
                      </TableCell>
                      <TableCell sx={{ width: 90, whiteSpace: 'nowrap' }}>
                        <Chip size="small" label={row.active ? 'Active' : 'Inactive'} color={row.active ? 'success' : 'default'} variant="outlined" />
                      </TableCell>
                      <TableCell sx={{ width: 60, whiteSpace: 'nowrap' }}>
                        <IconButton size="small" aria-label="actions" onClick={(e) => openRowMenu(e, row)}>
                          <MoreVertIcon fontSize="small" />
                        </IconButton>
                      </TableCell>
                    </TableRow>
                  );
                })}
                {rows.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={5}>
                      <Typography variant="body2" color="text.secondary" sx={{ py: 2, textAlign: 'center' }}>
                        No FAQs found.
                      </Typography>
                    </TableCell>
                  </TableRow>
                ) : null}
              </TableBody>
            </Table>
          </TableContainer>
        </Paper>
      </Stack>

      <Dialog open={openDialog} onClose={handleClose} maxWidth="md" fullWidth>
        <DialogTitle sx={{ fontWeight: 700 }}>
          <Stack direction="row" alignItems="center" spacing={1}>
            <HelpOutlineIcon fontSize="small" />
            <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
              {editing ? 'Edit FAQ' : 'Add FAQ'}
            </Typography>
          </Stack>
        </DialogTitle>
        <Divider />
        <DialogContent sx={{ pt: 2 }}>
          <Stack spacing={1.25}>
            <TextField
              label="Question"
              value={data.question}
              onChange={(e) => setData('question', e.target.value)}
              error={Boolean(errors.question)}
              helperText={errors.question}
              fullWidth
              size="small"
            />

            <TextField
              label="Answer"
              value={data.answer}
              onChange={(e) => setData('answer', e.target.value)}
              error={Boolean(errors.answer)}
              helperText={errors.answer}
              fullWidth
              size="small"
              multiline
              minRows={5}
            />

            <TextField
              label="Sort Order"
              type="number"
              value={data.sort_order}
              onChange={(e) => setData('sort_order', e.target.value)}
              error={Boolean(errors.sort_order)}
              helperText={errors.sort_order}
              fullWidth
              size="small"
              inputProps={{ min: 0 }}
            />

            <FormControlLabel
              control={<Switch checked={Boolean(data.active)} onChange={(e) => setData('active', e.target.checked)} />}
              label="Active"
            />
          </Stack>
        </DialogContent>
        <Divider />
        <DialogActions sx={{ px: 3, py: 2 }}>
          <Button onClick={handleClose} disabled={processing}>
            Cancel
          </Button>
          <Button variant="contained" onClick={handleSubmit} disabled={processing}>
            {editing ? 'Save Changes' : 'Create'}
          </Button>
        </DialogActions>
      </Dialog>

      <Menu
        anchorEl={rowMenu.anchorEl}
        open={rowMenuOpen}
        onClose={closeRowMenu}
        PaperProps={{
          sx: {
            minWidth: 160,
          },
        }}
      >
        <MenuItem
          onClick={() => {
            const row = rowMenu.row;
            closeRowMenu();
            if (row) handleOpenEdit(row);
          }}
        >
          <ListItemIcon>
            <EditIcon fontSize="small" />
          </ListItemIcon>
          <ListItemText>Edit</ListItemText>
        </MenuItem>
        <MenuItem
          onClick={() => {
            const row = rowMenu.row;
            closeRowMenu();
            if (row) handleDelete(row);
          }}
        >
          <ListItemIcon>
            <DeleteIcon fontSize="small" />
          </ListItemIcon>
          <ListItemText>Delete</ListItemText>
        </MenuItem>
      </Menu>
    </Box>
  );
}

Faqs.layout = (page) => <AdminLayout children={page} />;
