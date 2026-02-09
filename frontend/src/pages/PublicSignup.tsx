/**
 * PublicSignup — Public student registration page via company signup link.
 * Matches Laravel SignupController::create() + store().
 * Accessible at /signup/:key without authentication.
 */
import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
  fetchSignupLinkByKey,
  registerViaSignupLink,
  fetchTimezones,
} from '@/lib/api';
import { toast } from 'sonner';
import {
  Loader2, BookOpen, Building2, GraduationCap, AlertTriangle, CheckCircle2, ArrowLeft,
} from 'lucide-react';

export default function PublicSignup() {
  const { key } = useParams<{ key: string }>();
  const navigate = useNavigate();

  const [linkData, setLinkData] = useState<Awaited<ReturnType<typeof fetchSignupLinkByKey>>>(null);
  const [timezones, setTimezones] = useState<{ id: number; name: string; region: string }[]>([]);
  const [loading, setLoading] = useState(true);
  const [invalid, setInvalid] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [success, setSuccess] = useState(false);

  // Form state
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [timezone, setTimezone] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');

  useEffect(() => {
    if (!key) { setInvalid(true); setLoading(false); return; }
    Promise.all([
      fetchSignupLinkByKey(key),
      fetchTimezones(),
    ]).then(([link, tz]) => {
      if (!link) { setInvalid(true); } else { setLinkData(link); }
      setTimezones(tz);
      if (tz.length > 0 && !timezone) {
        const defaultTz = tz.find(t => t.name.includes('Australia/Sydney')) || tz[0];
        setTimezone(defaultTz.name);
      }
    }).finally(() => setLoading(false));
  }, [key]);

  const validate = (): string | null => {
    if (!firstName.trim()) return 'First name is required';
    if (!lastName.trim()) return 'Last name is required';
    if (!email.trim()) return 'Email is required';
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return 'Please enter a valid email address';
    if (!phone.trim()) return 'Phone is required';
    if (!/^[\+0-9]{6,}$/.test(phone.replace(/\s/g, ''))) return 'Please enter a valid phone number';
    if (!timezone) return 'Please select a timezone';
    if (password.length < 6) return 'Password must be at least 6 characters';
    if (password !== confirmPassword) return 'Passwords do not match';
    return null;
  };

  const handleSubmit = async () => {
    const err = validate();
    if (err) { toast.error(err); return; }
    if (!key) return;

    setSubmitting(true);
    try {
      const result = await registerViaSignupLink({
        signup_link_key: key,
        first_name: firstName.trim(),
        last_name: lastName.trim(),
        email: email.trim().toLowerCase(),
        phone: phone.trim(),
        timezone,
        password,
      });

      if (result.success) {
        setSuccess(true);
        toast.success(result.message);
      } else {
        toast.error(result.message);
      }
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Registration failed');
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-[#eff6ff] to-[#f8fafc] flex items-center justify-center">
        <Loader2 className="w-8 h-8 animate-spin text-[#3b82f6]" />
      </div>
    );
  }

  if (invalid || !linkData) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-[#eff6ff] to-[#f8fafc] flex items-center justify-center p-4">
        <Card className="max-w-md w-full">
          <CardContent className="py-12 text-center">
            <AlertTriangle className="w-12 h-12 text-amber-500 mx-auto mb-4" />
            <h2 className="text-lg font-bold text-[#1e293b] mb-2">Invalid Signup Link</h2>
            <p className="text-sm text-[#64748b] mb-6">
              This signup link is invalid or has been deactivated. Please contact your administrator for a new link.
            </p>
            <Button variant="outline" onClick={() => navigate('/')}>
              <ArrowLeft className="w-4 h-4 mr-1.5" /> Go to Login
            </Button>
          </CardContent>
        </Card>
      </div>
    );
  }

  if (success) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-[#eff6ff] to-[#f8fafc] flex items-center justify-center p-4">
        <Card className="max-w-md w-full">
          <CardContent className="py-12 text-center">
            <CheckCircle2 className="w-12 h-12 text-green-500 mx-auto mb-4" />
            <h2 className="text-lg font-bold text-[#1e293b] mb-2">Registration Successful!</h2>
            <p className="text-sm text-[#64748b] mb-2">
              Your account has been created successfully.
            </p>
            <p className="text-sm text-[#64748b] mb-6">
              Please check your email for a verification link, then log in to start your course.
            </p>
            <Button className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" onClick={() => navigate('/')}>
              Go to Login
            </Button>
          </CardContent>
        </Card>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-[#eff6ff] to-[#f8fafc] flex items-center justify-center p-4">
      <div className="max-w-lg w-full space-y-4">
        {/* Header Card */}
        <Card>
          <CardContent className="py-6">
            <div className="flex items-center gap-4">
              <div className="p-3 rounded-xl bg-[#eff6ff]">
                <GraduationCap className="w-8 h-8 text-[#3b82f6]" />
              </div>
              <div>
                <h1 className="text-xl font-bold text-[#1e293b]">Student Registration</h1>
                <div className="flex items-center gap-3 mt-1 text-sm text-[#64748b]">
                  <span className="flex items-center gap-1">
                    <Building2 className="w-3.5 h-3.5" /> {linkData.company_name}
                  </span>
                  <span className="flex items-center gap-1">
                    <BookOpen className="w-3.5 h-3.5" /> {linkData.course_title}
                  </span>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Registration Form */}
        <Card>
          <CardHeader>
            <CardTitle className="text-base text-[#3b82f6]">Create Your Account</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-2 gap-3">
              <div className="space-y-2">
                <Label>First Name <span className="text-red-500">*</span></Label>
                <Input
                  placeholder="John"
                  value={firstName}
                  onChange={(e) => setFirstName(e.target.value)}
                  disabled={submitting}
                />
              </div>
              <div className="space-y-2">
                <Label>Last Name <span className="text-red-500">*</span></Label>
                <Input
                  placeholder="Smith"
                  value={lastName}
                  onChange={(e) => setLastName(e.target.value)}
                  disabled={submitting}
                />
              </div>
            </div>

            <div className="space-y-2">
              <Label>Email <span className="text-red-500">*</span></Label>
              <Input
                type="email"
                placeholder="john.smith@example.com"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                disabled={submitting}
              />
            </div>

            <div className="space-y-2">
              <Label>Phone <span className="text-red-500">*</span></Label>
              <Input
                type="tel"
                placeholder="+61400000000"
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
                disabled={submitting}
              />
            </div>

            <div className="space-y-2">
              <Label>Timezone <span className="text-red-500">*</span></Label>
              <Select value={timezone} onValueChange={setTimezone} disabled={submitting}>
                <SelectTrigger>
                  <SelectValue placeholder="Select timezone" />
                </SelectTrigger>
                <SelectContent>
                  {timezones.map(tz => (
                    <SelectItem key={tz.id} value={tz.name}>{tz.name}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="grid grid-cols-2 gap-3">
              <div className="space-y-2">
                <Label>Password <span className="text-red-500">*</span></Label>
                <Input
                  type="password"
                  placeholder="Min 6 characters"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  disabled={submitting}
                />
              </div>
              <div className="space-y-2">
                <Label>Confirm Password <span className="text-red-500">*</span></Label>
                <Input
                  type="password"
                  placeholder="Confirm password"
                  value={confirmPassword}
                  onChange={(e) => setConfirmPassword(e.target.value)}
                  disabled={submitting}
                />
              </div>
            </div>

            <Button
              className="w-full bg-[#3b82f6] hover:bg-[#2563eb] text-white mt-2"
              disabled={submitting}
              onClick={handleSubmit}
            >
              {submitting ? (
                <><Loader2 className="w-4 h-4 mr-1.5 animate-spin" /> Creating Account...</>
              ) : (
                'Create Account'
              )}
            </Button>

            <p className="text-xs text-center text-[#94a3b8]">
              Already have an account?{' '}
              <button type="button" className="text-[#3b82f6] hover:underline" onClick={() => navigate('/')}>
                Log in
              </button>
            </p>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
