import Image from 'next/image'
import logoImage from '@/images/rp-logo.png'

export function Logo(props) {
  return (
    <Image
      src={logoImage}
      alt="Recruiting Playbook"
      width={200}
      height={40}
      className={props.className}
      unoptimized
    />
  )
}
