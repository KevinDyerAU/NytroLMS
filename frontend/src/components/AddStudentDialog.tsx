/**
 * AddStudentDialog — Create new student form matching Laravel's students/add-edit.blade.php
 * Fields: name, email, phone, address, language, company, leader, trainer, course, schedule, employment service
 */
import { useState, useEffect } from 'react';
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
  createStudent,
  fetchAllCompanies,
  fetchAllLeaders,
  fetchAllTrainers,
  fetchAvailableCourses,
  type StudentRelatedUser,
} from '@/lib/api';
import { Loader2 } from 'lucide-react';
import { toast } from 'sonner';

interface AddStudentDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onCreated: (studentId: number) => void;
}

const SCHEDULES = ['25 Hours', '15 Hours', '8 Hours', 'No Time Limit', 'Not Applicable'];
const EMPLOYMENT_SERVICES = [
  'Workforce Australia',
  'Inclusive Employment Australia (IEA)',
  'Transition to Work (TTW)',
  'Parent Pathways',
  'Other',
];

export function AddStudentDialog({ open, onOpenChange, onCreated }: AddStudentDialogProps) {
  const [saving, setSaving] = useState(false);
  const [companies, setCompanies] = useState<{ id: number; name: string }[]>([]);
  const [leaders, setLeaders] = useState<StudentRelatedUser[]>([]);
  const [trainers, setTrainers] = useState<StudentRelatedUser[]>([]);
  const [courses, setCourses] = useState<{ id: number; title: string; category: string | null; status: string }[]>([]);
  const [optionsLoaded, setOptionsLoaded] = useState(false);

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
  const [companyId, setCompanyId] = useState('');
  const [leaderId, setLeaderId] = useState('');
  const [trainerId, setTrainerId] = useState('');
  const [courseId, setCourseId] = useState('');
  const [schedule, setSchedule] = useState('');
  const [employmentService, setEmploymentService] = useState('');

  useEffect(() => {
    if (open && !optionsLoaded) {
      Promise.all([
        fetchAllCompanies(),
        fetchAllLeaders(),
        fetchAllTrainers(),
        fetchAvailableCourses(),
      ]).then(([c, l, t, co]) => {
        setCompanies(c);
        setLeaders(l);
        setTrainers(t);
        setCourses(co);
        setOptionsLoaded(true);
      }).catch(() => {
        toast.error('Failed to load form options');
      });
    }
  }, [open, optionsLoaded]);

  const resetForm = () => {
    setFirstName(''); setLastName(''); setPreferredName('');
    setEmail(''); setPhone(''); setAddress('');
    setLanguage('en'); setPreferredLanguage(''); setPurchaseOrder('');
    setStudyType(''); setCompanyId(''); setLeaderId('');
    setTrainerId(''); setCourseId(''); setSchedule('');
    setEmploymentService('');
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!firstName.trim() || !lastName.trim() || !email.trim()) {
      toast.error('First name, last name, and email are required');
      return;
    }
    if (!purchaseOrder.trim()) {
      toast.error('Purchase order number is required');
      return;
    }
    if (!companyId) {
      toast.error('Company/Site is required');
      return;
    }
    if (!courseId) {
      toast.error('Please select a course');
      return;
    }
    if (!schedule) {
      toast.error('Please select a schedule');
      return;
    }
    if (!employmentService) {
      toast.error('Please select an employment service');
      return;
    }

    setSaving(true);
    try {
      const result = await createStudent({
        first_name: firstName.trim(),
        last_name: lastName.trim(),
        email: email.trim(),
        phone: phone.trim() || undefined,
        address: address.trim() || undefined,
        language,
        preferred_language: preferredLanguage.trim() || undefined,
        preferred_name: preferredName.trim() || undefined,
        purchase_order: purchaseOrder.trim(),
        study_type: studyType || undefined,
        company_id: companyId ? Number(companyId) : undefined,
        leader_id: leaderId ? Number(leaderId) : undefined,
        trainer_id: trainerId ? Number(trainerId) : undefined,
        course_id: courseId ? Number(courseId) : undefined,
        schedule,
        employment_service: employmentService,
      });

      toast.success(`Student ${firstName} ${lastName} created successfully`);
      resetForm();
      onOpenChange(false);
      onCreated(result.id);
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to create student');
    } finally {
      setSaving(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Add New Student</DialogTitle>
        </DialogHeader>

        <form onSubmit={handleSubmit} className="space-y-4">
          {/* Personal Info */}
          <div className="space-y-3">
            <h3 className="text-sm font-semibold text-[#64748b] uppercase tracking-wider">Personal Information</h3>
            <div className="grid grid-cols-2 gap-3">
              <div>
                <Label htmlFor="firstName">First Name *</Label>
                <Input id="firstName" value={firstName} onChange={(e) => setFirstName(e.target.value)} placeholder="First name" required />
              </div>
              <div>
                <Label htmlFor="lastName">Last Name *</Label>
                <Input id="lastName" value={lastName} onChange={(e) => setLastName(e.target.value)} placeholder="Last name" required />
              </div>
            </div>
            <div className="grid grid-cols-2 gap-3">
              <div>
                <Label htmlFor="preferredName">Preferred Name</Label>
                <Input id="preferredName" value={preferredName} onChange={(e) => setPreferredName(e.target.value)} placeholder="Preferred name" />
              </div>
              <div>
                <Label htmlFor="email">Email *</Label>
                <Input id="email" type="email" value={email} onChange={(e) => setEmail(e.target.value)} placeholder="email@example.com" required />
              </div>
            </div>
            <div className="grid grid-cols-2 gap-3">
              <div>
                <Label htmlFor="phone">Phone</Label>
                <Input id="phone" value={phone} onChange={(e) => setPhone(e.target.value)} placeholder="+61..." />
              </div>
              <div>
                <Label htmlFor="purchaseOrder">Purchase Order Number *</Label>
                <Input id="purchaseOrder" value={purchaseOrder} onChange={(e) => setPurchaseOrder(e.target.value)} placeholder="PO number" required />
              </div>
            </div>
            <div>
              <Label htmlFor="address">Address</Label>
              <Input id="address" value={address} onChange={(e) => setAddress(e.target.value)} placeholder="Street address" />
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
                <Label htmlFor="preferredLanguage">Preferred Language</Label>
                <Input id="preferredLanguage" value={preferredLanguage} onChange={(e) => setPreferredLanguage(e.target.value)} placeholder="e.g. Arabic" />
              </div>
            </div>
          </div>

          {/* Assignment */}
          <div className="space-y-3">
            <h3 className="text-sm font-semibold text-[#64748b] uppercase tracking-wider">Assignment</h3>
            <div className="grid grid-cols-2 gap-3">
              <div>
                <Label>Company/Site *</Label>
                <Select value={companyId} onValueChange={setCompanyId}>
                  <SelectTrigger><SelectValue placeholder="Select company..." /></SelectTrigger>
                  <SelectContent>
                    {companies.map(c => (
                      <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div>
                <Label>Leader</Label>
                <Select value={leaderId} onValueChange={setLeaderId}>
                  <SelectTrigger><SelectValue placeholder="Select leader..." /></SelectTrigger>
                  <SelectContent>
                    {leaders.map(l => (
                      <SelectItem key={l.id} value={String(l.id)}>{l.first_name} {l.last_name}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>
            <div>
              <Label>Trainer</Label>
              <Select value={trainerId} onValueChange={setTrainerId}>
                <SelectTrigger><SelectValue placeholder="Select trainer..." /></SelectTrigger>
                <SelectContent>
                  {trainers.map(t => (
                    <SelectItem key={t.id} value={String(t.id)}>{t.first_name} {t.last_name}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>

          {/* Course & Enrolment */}
          <div className="space-y-3">
            <h3 className="text-sm font-semibold text-[#64748b] uppercase tracking-wider">Course Enrolment</h3>
            <div>
              <Label>Course *</Label>
              <Select value={courseId} onValueChange={setCourseId}>
                <SelectTrigger><SelectValue placeholder="Select course..." /></SelectTrigger>
                <SelectContent>
                  {courses.map(c => (
                    <SelectItem key={c.id} value={String(c.id)}>
                      {c.title} {c.category ? `(${c.category})` : ''}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="grid grid-cols-2 gap-3">
              <div>
                <Label>Schedule *</Label>
                <Select value={schedule} onValueChange={setSchedule}>
                  <SelectTrigger><SelectValue placeholder="Select schedule..." /></SelectTrigger>
                  <SelectContent>
                    {SCHEDULES.map(s => (
                      <SelectItem key={s} value={s}>{s}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div>
                <Label>Employment Service *</Label>
                <Select value={employmentService} onValueChange={setEmploymentService}>
                  <SelectTrigger><SelectValue placeholder="Select service..." /></SelectTrigger>
                  <SelectContent>
                    {EMPLOYMENT_SERVICES.map(es => (
                      <SelectItem key={es} value={es}>{es}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>
            <div>
              <Label>Study Type</Label>
              <Select value={studyType} onValueChange={setStudyType}>
                <SelectTrigger><SelectValue placeholder="Select study type..." /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="full-time">Full Time</SelectItem>
                  <SelectItem value="part-time">Part Time</SelectItem>
                  <SelectItem value="online">Online</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={saving}>
              Cancel
            </Button>
            <Button type="submit" className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" disabled={saving}>
              {saving && <Loader2 className="w-4 h-4 mr-1.5 animate-spin" />}
              Create Student
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
