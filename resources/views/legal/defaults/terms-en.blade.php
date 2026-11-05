<p>These Terms &amp; Conditions govern your use of the website and services of {{ $company }} (“we”). By creating an account or using this website, you confirm that you have read, understood and agreed to these Terms &amp; Conditions and our <a href="{{ route('legal.privacy', [], false) }}">Privacy Policy</a>.</p>

<h2>1. Our services</h2>
<p>We provide information on work programmes in Japan (Tokutei Ginou, Internship and Engineer), job openings from partner companies, and a platform for registration, document management and job applications. Language training is provided through a separate platform, {{ \App\Support\Brand::learning() }}.</p>

<h2>2. User accounts</h2>
<ul>
    <li>The information you provide must be true, complete and up to date.</li>
    <li>You are responsible for keeping your password confidential and for all activity on your account.</li>
    <li>Each person may hold only one account.</li>
</ul>

<h2>3. Jobs and selection</h2>
<ul>
    <li>Job information comes from partner companies and may change at any time.</li>
    <li>Hiring decisions rest entirely with the company in Japan.</li>
    <li>We support you through selection but do not guarantee passing an interview, receiving a visa or departure.</li>
</ul>

<h2>4. Fees and payment</h2>
<ul>
    <li>The only official fees are those listed on the <a href="{{ route('fees', [], false) }}">Programme Fees</a> page or in a written agreement you sign.</li>
    <li>Official payments go only to a bank account in the name of {{ $company }}, always with an official receipt.</li>
    <li>We are not responsible for payments made to anyone claiming to act for us outside our official channels.</li>
</ul>

<h2>5. Your obligations</h2>
<p>You must not:</p>
<ul>
    <li>Enter false information or upload forged documents or documents belonging to someone else.</li>
    <li>Use the website for any unlawful purpose.</li>
    <li>Disrupt the security or performance of the website, including automated data collection without permission.</li>
</ul>
<p>Falsified data or documents may lead to the cancellation of applications and closure of the account.</p>

<h2>6. Intellectual property</h2>
<p>All content on this website, including text, logos, images and design, belongs to {{ $company }} or its licensors and may not be used without written permission.</p>

<h2>7. Limitation of liability</h2>
<p>We aim to keep information accurate but cannot guarantee that the website is always free of errors or interruptions. To the extent permitted by law, we are not liable for indirect losses arising from the use of this website.</p>

<h2>8. Suspension and closure</h2>
<p>We may suspend or close accounts that breach these Terms &amp; Conditions. You may ask us to close your account at any time.</p>

<h2>9. Governing law</h2>
<p>These Terms &amp; Conditions are governed by the laws of the Republic of Indonesia. Disputes will first be settled amicably.</p>

<h2>10. Changes</h2>
<p>We may update these Terms &amp; Conditions from time to time. The latest version is always available on this page.</p>

<h2>11. Contact us</h2>
<p>{{ $company }}<br>
@if($email)Email: <a href="mailto:{{ $email }}">{{ $email }}</a><br>@endif
@if($phone)Phone/WhatsApp: {{ $phone }}<br>@endif
</p>
