/**
 * EditStudentDialog — Edit existing student fields matching Laravel's students/add-edit.blade.php
 */
import { useState, useEffect } from 'react';
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { updateStudent, fetchStudentFullDetail, type StudentFullDetail } from '@/lib/api';
import { Loader2 } from 'lucide-react';
import { toast } from 'sonner';

interface EditStudentDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  studentId: number;
  onSaved: () => void;
}

export function EditStudentDialog({ open, onOpenChange, studentId, onSaved }: EditStudentDialogProps) {
  const [saving, setSaving] = useState(false);
  const [loading, setLoading] = useState(true);
  const [student, setStudent] = useState<StudentFullDetail | null>(null);

  // Form state
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [preferredName, setPreferredName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [address, setAddress] = useState('');
  const [language, setLanguage] = useState('en');
  const [preferredLanguage, setPreferredLanguage] = useState('');
  const [purchaseOrder, setPurchaseOrder] = useState('');
  const [studyType, setStudyType] = useState('');

  useEffect(() => {
    if (open && studentId) {
      setLoading(true);
      fetchStudentFullDetail(studentId)
        .then((data) => {
          if (data) {
            setStudent(data);
            setFirstName(data.first_name || '');
            setLastName(data.last_name || '');
            setPreferredName(data.user_details?.preferred_name || '');
            setEmail(data.email || '');
            setPhone(data.user_details?.phone || '');
            setAddress(data.user_details?.address || '');
            setLanguage(data.user_details?.language || 'en');
            setPreferredLanguage(data.user_details?.preferred_language || '');
            setPurchaseOrder(data.user_details?.purchase_order || '');
            setStudyType(data.study_type || '');
          }
        })
        .catch(() => toast.error('Failed to load student data'))
        .finally(() => setLoading(false));
    }
  }, [open, studentId]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!firstName.trim() || !lastName.trim() || !email.trim()) {
      toast.error('First name, last name, and email are required');
      return;
    }

    setSaving(true);
    try {
      await updateStudent(studentId, {
        first_name: firstName.trim(),
        last_name: lastName.trim(),
        email: email.trim(),
        phone: phone.trim(),
        address: address.trim(),
        language,
        preferred_language: preferredLanguage.trim(),
        preferred_name: preferredName.trim(),
        purchase_order: purchaseOrder.trim(),
        study_type: studyType || undefined,
      });

      toast.success(`Student ${firstName} ${lastName} updated successfully`);
      onOpenChange(false);
      onSaved();
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to update student');
    } finally {
      setSaving(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>
            Edit Student {student ? `— ${student.first_name} ${student.last_name}` : ''}
          </DialogTitle>
        </DialogHeader>

        {loading ? (
          <div className="flex items-center justify-center py-12">
            <Loader2 className="w-6 h-6 animate-spin text-[#3b82f6]" />
          </div>
        ) : (
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="space-y-3">
              <h3 className="text-sm font-semibold text-[#64748b] uppercase tracking-wider">Personal Information</h3>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <Label htmlFor="editFirstName">First Name *</Label>
                  <Input id="editFirstName" value={firstName} onChange={(e) => setFirstName(e.target.value)} required />
                </div>
                <div>
                  <Label htmlFor="editLastName">Last Name *</Label>
                  <Input id="editLastName" value={lastName} onChange={(e) => setLastName(e.target.value)} required />
                </div>
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <Label htmlFor="editPreferredName">Preferred Name</Label>
                  <Input id="editPreferredName" value={preferredName} onChange={(e) => setPreferredName(e.target.value)} />
                </div>
                <div>
                  <Label htmlFor="editEmail">Email *</Label>
                  <Input id="editEmail" type="email" value={email} onChange={(e) => setEmail(e.target.value)} required />
                </div>
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <Label htmlFor="editPhone">Phone</Label>
                  <Input id="editPhone" value={phone} onChange={(e) => setPhone(e.target.value)} />
                </div>
                <div>
                  <Label htmlFor="editPurchaseOrder">Purchase Order Number</Label>
                  <Input id="editPurchaseOrder" value={purchaseOrder} onChange={(e) => setPurchaseOrder(e.target.value)} />
                </div>
              </div>
              <div>
                <Label htmlFor="editAddress">Address</Label>
                <Input id="editAddress" value={address} onChange={(e) => setAddress(e.target.value)} />
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <Label>Language</Label>
                  <Select value={language} onValueChange={setLanguage}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="en">English</SelectItem>
                      <SelectItem value="ar">Arabic</SelectItem>
                      <SelectItem value="zh">Chinese</SelectItem>
                      <SelectItem value="vi">Vietnamese</SelectItem>
                      <SelectItem value="other">Other</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div>
                  <Label htmlFor="editPreferredLanguage">Preferred Language</Label>
                  <Input id="editPreferredLanguage" value={preferredLanguage} onChange={(e) => setPreferredLanguage(e.target.value)} />
                </div>
              </div>
              <div>
                <Label>Study Type</Label>
                <Select value={studyType || '_none'} onValueChange={(v) => setStudyType(v === '_none' ? '' : v)}>
                  <SelectTrigger><SelectValue placeholder="Select study type..." /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="_none">None</SelectItem>
                    <SelectItem value="full-time">Full Time</SelectItem>
                    <SelectItem value="part-time">Part Time</SelectItem>
                    <SelectItem value="online">Online</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            {/* Read-only info */}
            {student && (
              <div className="space-y-2 pt-2 border-t border-[#3b82f6]/10">
                <h3 className="text-sm font-semibold text-[#64748b] uppercase tracking-wider">Assignments (read-only)</h3>
                <div className="grid grid-cols-2 gap-2 text-sm text-[#64748b]">
                  <div>
                    <span className="font-medium">Company:</span>{' '}
                    {student.companies.length > 0 ? student.companies.map(c => c.name).join(', ') : '—'}
                  </div>
                  <div>
                    <span className="font-medium">Leader:</span>{' '}
                    {student.leaders.length > 0 ? student.leaders.map(l => `${l.first_name} ${l.last_name}`).join(', ') : '—'}
                  </div>
                  <div>
                    <span className="font-medium">Trainer:</span>{' '}
                    {student.trainers.length > 0 ? student.trainers.map(t => `${t.first_name} ${t.last_name}`).join(', ') : '—'}
                  </div>
                  <div>
                    <span className="font-medium">Courses:</span>{' '}
                    {student.enrolments.length > 0 ? student.enrolments.map(e => e.course_title).join(', ') : '—'}
                  </div>
                </div>
              </div>
            )}

            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={saving}>
                Cancel
              </Button>
              <Button type="submit" className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" disabled={saving}>
                {saving && <Loader2 className="w-4 h-4 mr-1.5 animate-spin" />}
                Save Changes
              </Button>
            </DialogFooter>
          </form>
        )}
      </DialogContent>
    </Dialog>
  );
}
